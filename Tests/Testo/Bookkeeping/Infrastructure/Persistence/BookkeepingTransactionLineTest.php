<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\Persistence;

use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\DebitCredit;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransaction;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransactionLine;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class BookkeepingTransactionLineTest
{
    public function defaultsToUnpersisted(): void
    {
        $line = new BookkeepingTransactionLine();

        Assert::false($line->isPersisted());
        Assert::same(AccountRole::Bank, $line->getAccount());
        Assert::same(DebitCredit::Debit, $line->getDirection());
        Assert::same(0.00, $line->getAmount());
        Assert::null($line->getTaxCode());
        Assert::null($line->getDescription());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new BookkeepingTransactionLine())->reqId();
    }

    #[ExpectException(\LogicException::class)]
    public function reqBookkeepingTransactionIdThrowsWhenNotSet(): void
    {
        (new BookkeepingTransactionLine())->reqBookkeepingTransactionId();
    }

    public function setIdMakesEntityPersisted(): void
    {
        $line = new BookkeepingTransactionLine();
        $line->setId(3);

        Assert::true($line->isPersisted());
        Assert::same(3, $line->reqId());
    }

    public function constructorSetsAllFields(): void
    {
        $line = new BookkeepingTransactionLine(
            bookkeeping_transaction_id: 7,
            account: AccountRole::VatOrTax->value,
            direction: DebitCredit::Credit->value,
            amount: 20.00,
            tax_code: 'GB_STANDARD',
            description: 'VAT on INV-1024',
        );

        Assert::same(7, $line->reqBookkeepingTransactionId());
        Assert::same(AccountRole::VatOrTax, $line->getAccount());
        Assert::same(DebitCredit::Credit, $line->getDirection());
        Assert::same(20.00, $line->getAmount());
        Assert::same('GB_STANDARD', $line->getTaxCode());
        Assert::same('VAT on INV-1024', $line->getDescription());
    }

    public function settersMutateValues(): void
    {
        $line = new BookkeepingTransactionLine();
        $line->setBookkeepingTransactionId(9);
        $line->setAccount(AccountRole::Sales);
        $line->setDirection(DebitCredit::Credit);
        $line->setAmount(100.00);
        $line->setTaxCode('GB_ZERO');
        $line->setDescription('Sale');

        Assert::same(9, $line->reqBookkeepingTransactionId());
        Assert::same(AccountRole::Sales, $line->getAccount());
        Assert::same(DebitCredit::Credit, $line->getDirection());
        Assert::same(100.00, $line->getAmount());
        Assert::same('GB_ZERO', $line->getTaxCode());
        Assert::same('Sale', $line->getDescription());
    }

    public function bookkeepingTransactionRelationIsSettable(): void
    {
        $transaction = new BookkeepingTransaction(
            type: 'invoice_issued',
            reference: 'INV-1024',
            date: new \DateTimeImmutable('2026-09-18'),
            currency: 'GBP',
        );
        $transaction->setId(7);

        $line = new BookkeepingTransactionLine();
        $line->setBookkeepingTransaction($transaction);

        Assert::same($transaction, $line->getBookkeepingTransaction());
    }
}
