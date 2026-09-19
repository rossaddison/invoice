<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;

/**
 * Brings the bookkeeping ledger in line with invoice state, whichever code
 * path changed that state (email, batch, API, manual payment, gateway).
 * Idempotent: every entry is keyed by a reference derived from the invoice
 * number, so running it repeatedly never duplicates anything.
 *
 * Issued (2), viewed (3) and dunning stages (5-11, 13) post the invoice
 * entry; paid (4) additionally posts the payment; void (14) reverses the
 * invoice entry; a credit note (creditinvoice_parent_id set) posts its own
 * reduction instead of an invoice entry. Refunds are posted at refund time
 * by PaymentRefundController, not here.
 */
final readonly class InvLedgerSyncService
{
    private const int STATUS_PAID = 4;
    private const int STATUS_CREDIT_NOTE = 12;
    private const int STATUS_VOID = 14;

    public function __construct(
        private InvRepository $invRepository,
        private InvBookkeepingTransactionFactory $factory,
    ) {
    }

    public function sync(): int
    {
        $count = 0;
        foreach ($this->invRepository->repoNonDraftLoadedInvAmount() as $invoice) {
            $this->syncInvoice($invoice);
            $count++;
        }
        return $count;
    }

    private function syncInvoice(\App\Infrastructure\Persistence\Inv\Inv $invoice): void
    {
        $amount = $invoice->getInvAmount();
        $status = $invoice->reqStatusId();

        if ($status === self::STATUS_CREDIT_NOTE || ($invoice->getCreditinvoiceParentId() ?? 0) > 0) {
            $this->factory->createForCreditNote($invoice, $amount);
            return;
        }
        if ($status === self::STATUS_VOID) {
            $this->factory->createForVoidedInvoice($invoice, $amount);
            return;
        }

        $this->factory->createForIssuedInvoice($invoice, $amount);
        if ($status === self::STATUS_PAID) {
            $this->factory->createForPaymentReceived($invoice, $amount);
        }
    }
}
