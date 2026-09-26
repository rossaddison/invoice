<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\FrontAccounting;

use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookup;
use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupKind;
use DateTimeImmutable;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class FrontAccountingLookupTest
{
    private const string OTHER_DATE = '2026-09-19';

    private function lookup(): FrontAccountingLookup
    {
        return new FrontAccountingLookup(
            FrontAccountingLookupKind::TaxGroup->value,
            '2',
            'Zero-rated',
            new DateTimeImmutable('2026-09-18'),
        );
    }

    public function defaultsToUnpersisted(): void
    {
        Assert::false($this->lookup()->isPersisted());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        $this->lookup()->reqId();
    }

    public function setIdMakesEntityPersisted(): void
    {
        $lookup = $this->lookup();
        $lookup->setId(7);

        Assert::true($lookup->isPersisted());
        Assert::same(7, $lookup->reqId());
    }

    public function constructorSetsAllFields(): void
    {
        $lookup = $this->lookup();

        Assert::same(FrontAccountingLookupKind::TaxGroup, $lookup->getKind());
        Assert::same('2', $lookup->getExternalId());
        Assert::same('Zero-rated', $lookup->getLabel());
        Assert::same('2026-09-18', $lookup->getSyncedAt()->format('Y-m-d'));
    }

    public function settersMutateValues(): void
    {
        $lookup = $this->lookup();
        $lookup->setKind(FrontAccountingLookupKind::BankAccount);
        $lookup->setExternalId('9');
        $lookup->setLabel('Main Account');
        $syncedAt = new DateTimeImmutable(self::OTHER_DATE);
        $lookup->setSyncedAt($syncedAt);

        Assert::same(FrontAccountingLookupKind::BankAccount, $lookup->getKind());
        Assert::same('9', $lookup->getExternalId());
        Assert::same('Main Account', $lookup->getLabel());
        Assert::same($syncedAt, $lookup->getSyncedAt());
    }
}
