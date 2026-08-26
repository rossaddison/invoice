<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\StockMovement\StockMovement;
use App\Invoice\Enum\StockMovementType;

/**
 * The one place a payment confirmation should settle an already-loaded,
 * already-confirmed-paid Inv — not the ~17 separate payment-gateway webhook
 * handlers (StripeWebhookHandler, AdyenWebhookHandler, etc.) that currently
 * duplicate this exact block themselves:
 *
 *   $invoice->setStatusId(4);
 *   $invoice->setPaymentMethod(4);
 *   $iR->save($invoice);
 *   $invoiceAmountRecord->setBalance(0);
 *   $invoiceAmountRecord->setPaid($invoiceAmountRecord->getTotal() ?? 0.00);
 *   $iaR->save($invoiceAmountRecord);
 *
 * — with no stock consequence at all today. Centralizing here means the
 * StockMovement side effect exists in exactly one place rather than needing
 * to be copy-pasted into every handler (and kept in sync with it forever).
 * See docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md.
 *
 * Deliberately takes the Inv/InvAmount objects the caller already loaded
 * (and, in most callers, already ran an idempotency check against — e.g.
 * StripeWebhookHandler::resolveContext() skips entirely once balance is
 * already 0.00) rather than re-fetching by id: every handler already holds
 * an in-memory copy fetched earlier in the same request, sometimes with
 * other fields already mutated on it (e.g. Braintree's payment_method_nonce
 * flow). A second, independently-loaded copy saved in the same request is
 * how a field silently gets clobbered by whichever save() runs last — not
 * a theoretical risk, this is the exact shape every one of these handlers
 * already has.
 *
 * Deliberately named for all of its effects, not just the status change —
 * `markInvoicePaid()` alone would hide that the balance and stock are
 * touched too.
 *
 * Not yet wired into any webhook handler.
 */
final readonly class InvPaymentSettlementService
{
    public function __construct(private InvPaymentSettlementDeps $d)
    {
    }

    public function markInvoicePaidAndAdjustStock(
        Inv $invoice,
        InvAmount $invoiceAmountRecord,
        int $paymentMethod = 4,
    ): void {
        $this->d->invService->withTransaction(
            function () use ($invoice, $invoiceAmountRecord, $paymentMethod): void {
                // Idempotency net: most callers already guard against a
                // re-fired webhook upstream (e.g. via balance === 0.00), but
                // an invoice already at status_id 4 must never be settled or
                // have stock recorded a second time regardless.
                if ($invoice->reqStatusId() === 4) {
                    return;
                }
                $this->applyPaidStatus($invoice, $paymentMethod);
                $this->settleInvoiceAmount($invoiceAmountRecord);
                $this->recordStockMovementsForSale($invoice);
            },
        );
    }

    private function applyPaidStatus(Inv $invoice, int $paymentMethod): void
    {
        $invoice->setStatusId(4);
        $invoice->setPaymentMethod($paymentMethod);
        $this->d->iR->save($invoice);
    }

    private function settleInvoiceAmount(InvAmount $invoiceAmountRecord): void
    {
        $invoiceAmountRecord->setBalance(0.00);
        $invoiceAmountRecord->setPaid($invoiceAmountRecord->getTotal() ?? 0.00);
        $this->d->iaR->save($invoiceAmountRecord);
    }

    /**
     * One StockMovement per InvItem whose Product still tracks stock
     * (Product::isTrackStock() — false for services and any product opted
     * out of tracking). Also applies the delta to Product::stock_quantity,
     * the denormalized cache of StockMovementRepository::currentBalance(),
     * in the same transaction as the ledger row.
     */
    private function recordStockMovementsForSale(Inv $invoice): void
    {
        $invId = $invoice->reqId();
        /** @var InvItem $item */
        foreach ($this->d->iiR->repoInvquery($invId) as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity() ?? 0.0;
            if ($product === null || !$product->isTrackStock() || $quantity <= 0.0) {
                continue;
            }

            $number = $invoice->getNumber();
            $reference = ($number !== null && $number !== '') ? $number : '#' . $invId;
            $movement = new StockMovement(
                product_id: $product->reqId(),
                type: StockMovementType::Sale->value,
                quantity_delta: -$quantity,
                inv_id: $invId,
                note: 'Invoice ' . $reference . ' paid',
            );
            $movement->setProduct($product);
            $movement->setInv($invoice);
            $this->d->smR->save($movement);

            $stockBeforeSale = $product->getStockQuantity();
            $product->setStockQuantity($stockBeforeSale - $quantity);
            $this->d->pR->save($product);
            $this->d->lowStockNotifier->notifyIfCrossed($product, $stockBeforeSale);
        }
    }
}
