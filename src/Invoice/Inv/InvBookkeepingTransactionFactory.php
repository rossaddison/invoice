<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Bookkeeping\Application\BookkeepingTransactionRepositoryInterface;
use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\BookkeepingTransaction;
use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Domain\DebitCredit;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Setting\SettingRepository;
use DateTimeImmutable;

/**
 * Turns a just-settled Inv into the BookkeepingTransactions the
 * Bookkeeping module needs to have anything to export (or to simply
 * exist as this app's own ledger, independent of whether any provider
 * gateway is even configured -- see BookkeepingService's own
 * isConfigured() no-op path). Called from
 * InvPaymentSettlementService::markInvoicePaidAndAdjustStock(), the one
 * place across this app's ~17 payment-gateway webhook handlers that
 * settles an Inv, matching how that same method already centralizes the
 * StockMovement side effect.
 *
 * Sources both lines' amounts from InvAmount at the point of settlement
 * rather than from a separate Payment record: settleInvoiceAmount()
 * (called immediately before this factory, in the same transaction)
 * already sets paid = total and balance = 0, i.e. "the amount paid" and
 * "the invoice total" are the same figure by construction of this app's
 * existing all-or-nothing settlement model (it does not yet model partial
 * payment/reconciliation). Using InvAmount directly avoids adding a
 * Payment parameter to markInvoicePaidAndAdjustStock()'s signature across
 * every webhook handler for a scenario this app doesn't have yet.
 */
final readonly class InvBookkeepingTransactionFactory
{
    public function __construct(
        private BookkeepingTransactionRepositoryInterface $bookkeepingTransactions,
        private SettingRepository $settingRepository,
    ) {
    }

    public function createForPaidInvoice(Inv $invoice, InvAmount $invoiceAmountRecord): void
    {
        $total = $invoiceAmountRecord->getTotal() ?? 0.00;
        if ($total <= 0.00) {
            return;
        }

        $number = $invoice->getNumber();
        $reference = ($number !== null && $number !== '') ? $number : '#' . $invoice->reqId();
        $currency = $this->settingRepository->getSetting('currency_code_from');
        $date = new DateTimeImmutable();
        $invId = $invoice->reqId();
        $taxTotal = $invoiceAmountRecord->getTaxTotal() ?? 0.00;

        $this->createInvoiceIssued($reference, $currency, $date, $invId, $total, $taxTotal);
        $this->createPaymentReceived($reference, $currency, $date, $invId, $total);
    }

    public function createForIssuedInvoice(Inv $invoice, InvAmount $invoiceAmountRecord): void
    {
        $total = $invoiceAmountRecord->getTotal() ?? 0.00;
        if ($total <= 0.00) {
            return;
        }
        $this->createInvoiceIssued(
            $this->referenceFor($invoice),
            $this->settingRepository->getSetting('currency_code_from'),
            $invoice->getDateCreated(),
            $invoice->reqId(),
            $total,
            $invoiceAmountRecord->getTaxTotal() ?? 0.00,
        );
    }

    public function createForPaymentReceived(Inv $invoice, InvAmount $invoiceAmountRecord): void
    {
        $total = $invoiceAmountRecord->getTotal() ?? 0.00;
        if ($total <= 0.00) {
            return;
        }
        $this->createPaymentReceived(
            $this->referenceFor($invoice),
            $this->settingRepository->getSetting('currency_code_from'),
            new DateTimeImmutable(),
            $invoice->reqId(),
            $total,
        );
    }

    /**
     * Reverses the invoice's InvoiceIssued entry. Does nothing when that
     * entry was never created (issued and voided between two syncs).
     */
    public function createForVoidedInvoice(Inv $invoice, InvAmount $invoiceAmountRecord): void
    {
        $reference = $this->referenceFor($invoice);
        if ($this->bookkeepingTransactions->findByReference($reference . '-invoice') === null) {
            return;
        }
        $this->createReversal(
            BookkeepingTransactionType::Void,
            $reference . '-void',
            new DateTimeImmutable(),
            $invoice->reqId(),
            $invoiceAmountRecord->getTotal() ?? 0.00,
            $invoiceAmountRecord->getTaxTotal() ?? 0.00,
        );
    }

    /**
     * A credit note is an invoice with negative amounts; its ledger entry is
     * the reduction of the amount owed (debit Sales and VAT, credit AR).
     */
    public function createForCreditNote(Inv $invoice, InvAmount $invoiceAmountRecord): void
    {
        $this->createReversal(
            BookkeepingTransactionType::CreditNote,
            $this->referenceFor($invoice) . '-creditnote',
            $invoice->getDateCreated(),
            $invoice->reqId(),
            abs($invoiceAmountRecord->getTotal() ?? 0.00),
            abs($invoiceAmountRecord->getTaxTotal() ?? 0.00),
        );
    }

    private function referenceFor(Inv $invoice): string
    {
        $number = $invoice->getNumber();
        return ($number !== null && $number !== '') ? $number : '#' . $invoice->reqId();
    }

    private function createReversal(
        BookkeepingTransactionType $type,
        string $transactionReference,
        DateTimeImmutable $date,
        int $invId,
        float $total,
        float $taxTotal,
    ): void {
        if ($total <= 0.00 || $this->bookkeepingTransactions->findByReference($transactionReference) !== null) {
            return;
        }
        $net = $total - $taxTotal;
        $lines = [];
        if ($net > 0.00) {
            $lines[] = new BookkeepingLine(AccountRole::Sales, DebitCredit::Debit, $net);
        }
        if ($taxTotal > 0.00) {
            $lines[] = new BookkeepingLine(AccountRole::VatOrTax, DebitCredit::Debit, $taxTotal);
        }
        $lines[] = new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, $total);

        $this->bookkeepingTransactions->save(new BookkeepingTransaction(
            $type,
            $transactionReference,
            $date,
            $this->settingRepository->getSetting('currency_code_from'),
            $lines,
            $invId,
        ));
    }

    public function createForRefundedInvoice(Inv $invoice, InvAmount $invoiceAmountRecord, float $refundedAmount): void
    {
        $total = $invoiceAmountRecord->getTotal() ?? 0.00;
        if ($refundedAmount <= 0.00 || $total <= 0.00) {
            return;
        }

        $number = $invoice->getNumber();
        $reference = ($number !== null && $number !== '') ? $number : '#' . $invoice->reqId();
        $transactionReference = $reference . '-refund';
        if ($this->bookkeepingTransactions->findByReference($transactionReference) !== null) {
            return;
        }

        $taxRefunded = round(($invoiceAmountRecord->getTaxTotal() ?? 0.00) * min(1.0, $refundedAmount / $total), 2);
        $net = $refundedAmount - $taxRefunded;
        $lines = [];
        if ($net > 0.00) {
            $lines[] = new BookkeepingLine(AccountRole::Sales, DebitCredit::Debit, $net);
        }
        if ($taxRefunded > 0.00) {
            $lines[] = new BookkeepingLine(AccountRole::VatOrTax, DebitCredit::Debit, $taxRefunded);
        }
        $lines[] = new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, $refundedAmount);
        $lines[] = new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Debit, $refundedAmount);
        $lines[] = new BookkeepingLine(AccountRole::Bank, DebitCredit::Credit, $refundedAmount);

        $this->bookkeepingTransactions->save(new BookkeepingTransaction(
            BookkeepingTransactionType::Refund,
            $transactionReference,
            new DateTimeImmutable(),
            $this->settingRepository->getSetting('currency_code_from'),
            $lines,
            $invoice->reqId(),
        ));
    }

    private function createInvoiceIssued(
        string $reference,
        string $currency,
        DateTimeImmutable $date,
        int $invId,
        float $total,
        float $taxTotal,
    ): void {
        $transactionReference = $reference . '-invoice';
        if ($this->bookkeepingTransactions->findByReference($transactionReference) !== null) {
            return;
        }

        $net = $total - $taxTotal;
        $lines = [new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Debit, $total)];
        if ($net > 0.00) {
            $lines[] = new BookkeepingLine(AccountRole::Sales, DebitCredit::Credit, $net);
        }
        if ($taxTotal > 0.00) {
            $lines[] = new BookkeepingLine(AccountRole::VatOrTax, DebitCredit::Credit, $taxTotal);
        }

        $this->bookkeepingTransactions->save(new BookkeepingTransaction(
            BookkeepingTransactionType::InvoiceIssued,
            $transactionReference,
            $date,
            $currency,
            $lines,
            $invId,
        ));
    }

    private function createPaymentReceived(
        string $reference,
        string $currency,
        DateTimeImmutable $date,
        int $invId,
        float $total,
    ): void {
        $transactionReference = $reference . '-payment';
        if ($this->bookkeepingTransactions->findByReference($transactionReference) !== null) {
            return;
        }

        $lines = [
            new BookkeepingLine(AccountRole::Bank, DebitCredit::Debit, $total),
            new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, $total),
        ];

        $this->bookkeepingTransactions->save(new BookkeepingTransaction(
            BookkeepingTransactionType::PaymentReceived,
            $transactionReference,
            $date,
            $currency,
            $lines,
            $invId,
        ));
    }
}
