<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\Persistence;

use App\Bookkeeping\Domain\BookkeepingTransactionType;
use App\Bookkeeping\Infrastructure\Persistence\BookkeepingTransaction;
use App\Infrastructure\Persistence\Inv\Inv;
use DateTimeImmutable;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class BookkeepingTransactionTest
{
    private const string OTHER_DATE = '2026-09-19';

    private function transaction(): BookkeepingTransaction
    {
        return new BookkeepingTransaction(
            type: BookkeepingTransactionType::InvoiceIssued->value,
            reference: 'INV-1024',
            date: new DateTimeImmutable('2026-09-18'),
            currency: 'GBP',
            inv_id: 1024,
        );
    }

    public function defaultsToUnpersistedAndNotExported(): void
    {
        $transaction = $this->transaction();

        Assert::false($transaction->isPersisted());
        Assert::false($transaction->isExported());
        Assert::null($transaction->getExportedProviderKey());
        Assert::null($transaction->getExportedProviderReference());
        Assert::null($transaction->getExportedAt());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        $this->transaction()->reqId();
    }

    public function setIdMakesEntityPersisted(): void
    {
        $transaction = $this->transaction();
        $transaction->setId(7);

        Assert::true($transaction->isPersisted());
        Assert::same(7, $transaction->reqId());
    }

    public function constructorSetsAllFields(): void
    {
        $transaction = $this->transaction();

        Assert::same(BookkeepingTransactionType::InvoiceIssued, $transaction->getType());
        Assert::same('INV-1024', $transaction->getReference());
        Assert::same('2026-09-18', $transaction->getDate()->format('Y-m-d'));
        Assert::same('GBP', $transaction->getCurrency());
        Assert::same(1024, $transaction->getInvId());
        Assert::instanceOf($transaction->getCreatedAt(), DateTimeImmutable::class);
    }

    public function settersMutateValues(): void
    {
        $transaction = $this->transaction();
        $transaction->setType(BookkeepingTransactionType::Refund);
        $transaction->setReference('INV-1025');
        $transaction->setDate(new DateTimeImmutable(self::OTHER_DATE));
        $transaction->setCurrency('EUR');
        $transaction->setInvId(1025);

        Assert::same(BookkeepingTransactionType::Refund, $transaction->getType());
        Assert::same('INV-1025', $transaction->getReference());
        Assert::same(self::OTHER_DATE, $transaction->getDate()->format('Y-m-d'));
        Assert::same('EUR', $transaction->getCurrency());
        Assert::same(1025, $transaction->getInvId());
    }

    public function exportSettersMakeItExported(): void
    {
        $transaction = $this->transaction();
        $exportedAt = new DateTimeImmutable(self::OTHER_DATE);
        $transaction->setExportedProviderKey('xero');
        $transaction->setExportedProviderReference('XERO-9001');
        $transaction->setExportedAt($exportedAt);

        Assert::true($transaction->isExported());
        Assert::same('xero', $transaction->getExportedProviderKey());
        Assert::same('XERO-9001', $transaction->getExportedProviderReference());
        Assert::same($exportedAt, $transaction->getExportedAt());
    }

    public function invRelationIsSettable(): void
    {
        $inv = new Inv();
        $inv->setId(1024);

        $transaction = $this->transaction();
        $transaction->setInv($inv);

        Assert::same($inv, $transaction->getInv());
    }
}
