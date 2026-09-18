<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Domain;

use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\BookkeepingTransaction;
use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Domain\DebitCredit;
use DateTimeImmutable;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class BookkeepingTransactionTest
{
    private const string TEST_DATE = '2026-09-18';

    /** @return list<BookkeepingLine> */
    private function balancedInvoiceIssuedLines(): array
    {
        return [
            new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Debit, 120.00),
            new BookkeepingLine(AccountRole::Sales, DebitCredit::Credit, 100.00),
            new BookkeepingLine(AccountRole::Vat, DebitCredit::Credit, 20.00),
        ];
    }

    /**
     * @param list<BookkeepingLine> $lines
     */
    private function transaction(array $lines): BookkeepingTransaction
    {
        return new BookkeepingTransaction(
            BookkeepingTransactionType::InvoiceIssued,
            'INV-1024',
            new DateTimeImmutable(self::TEST_DATE),
            'GBP',
            $lines,
            1024,
        );
    }

    public function constructsWhenDebitsEqualCredits(): void
    {
        $transaction = $this->transaction($this->balancedInvoiceIssuedLines());

        Assert::same(BookkeepingTransactionType::InvoiceIssued, $transaction->getType());
        Assert::same('INV-1024', $transaction->getReference());
        Assert::same(self::TEST_DATE, $transaction->getDate()->format('Y-m-d'));
        Assert::same('GBP', $transaction->getCurrency());
        Assert::same(1024, $transaction->getSourceInvId());
        Assert::count($transaction->getLines(), 3);
    }

    public function toleratesRoundingWithinHalfAPenny(): void
    {
        $lines = [
            new BookkeepingLine(AccountRole::Bank, DebitCredit::Debit, 100.004),
            new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, 100.001),
        ];

        Assert::instanceOf($this->transaction($lines), BookkeepingTransaction::class);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsUnbalancedLines(): void
    {
        $lines = [
            new BookkeepingLine(AccountRole::Bank, DebitCredit::Debit, 100.00),
            new BookkeepingLine(AccountRole::AccountsReceivable, DebitCredit::Credit, 90.00),
        ];

        // NOSONAR php:S1848 — the constructor throwing is the assertion
        $this->transaction($lines);
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsAnEmptyLineList(): void
    {
        // NOSONAR php:S1848 — the constructor throwing is the assertion
        $this->transaction([]);
    }

    public function defaultsToUnpersisted(): void
    {
        $transaction = $this->transaction($this->balancedInvoiceIssuedLines());

        Assert::false($transaction->isPersisted());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        $this->transaction($this->balancedInvoiceIssuedLines())->reqId();
    }

    public function setIdMakesTransactionPersisted(): void
    {
        $transaction = $this->transaction($this->balancedInvoiceIssuedLines());
        $transaction->setId(7);

        Assert::true($transaction->isPersisted());
        Assert::same(7, $transaction->reqId());
    }

    public function defaultsToNotExported(): void
    {
        $transaction = $this->transaction($this->balancedInvoiceIssuedLines());

        Assert::false($transaction->isExported());
        Assert::null($transaction->getExportedProviderKey());
        Assert::null($transaction->getExportedProviderReference());
        Assert::null($transaction->getExportedAt());
    }

    public function markExportedRecordsProviderKeyReferenceAndTimestamp(): void
    {
        $transaction = $this->transaction($this->balancedInvoiceIssuedLines());
        $exportedAt = new DateTimeImmutable('2026-09-19');

        $transaction->markExported('xero', 'XERO-INV-4001', $exportedAt);

        Assert::true($transaction->isExported());
        Assert::same('xero', $transaction->getExportedProviderKey());
        Assert::same('XERO-INV-4001', $transaction->getExportedProviderReference());
        Assert::same($exportedAt, $transaction->getExportedAt());
    }

    public function markExportedCanBeCalledAgainToReconfirm(): void
    {
        // A later run's getTransaction() lookup finding it already posted
        // (e.g. a previous createTransaction() succeeded but the response
        // was lost to a timeout) re-confirms with the same facts rather
        // than needing a guard against double-marking.
        $transaction = $this->transaction($this->balancedInvoiceIssuedLines());
        $transaction->markExported('xero', 'XERO-INV-4001', new DateTimeImmutable('2026-09-19'));

        $secondConfirmation = new DateTimeImmutable('2026-09-20');
        $transaction->markExported('xero', 'XERO-INV-4001', $secondConfirmation);

        Assert::same($secondConfirmation, $transaction->getExportedAt());
    }

    public function sourceInvIdDefaultsToNullForEventsWithNoOwningInvoice(): void
    {
        // NOSONAR php:S1848 — instantiation exercises the constructor path with no $sourceInvId argument
        $transaction = new BookkeepingTransaction(
            BookkeepingTransactionType::MerchantFee,
            'FEE-9001',
            new DateTimeImmutable(self::TEST_DATE),
            'GBP',
            [
                new BookkeepingLine(AccountRole::PaymentFees, DebitCredit::Debit, 2.50),
                new BookkeepingLine(AccountRole::Bank, DebitCredit::Credit, 2.50),
            ],
        );

        Assert::null($transaction->getSourceInvId());
    }
}
