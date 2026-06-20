<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\Family;

use App\Infrastructure\Persistence\Family\Family;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class FamilyTest
{
    public function defaultsToUnpersisted(): void
    {
        $family = new Family();

        Assert::false($family->hasIdentity());
        Assert::null($family->getFamilyName() === '' ? null : $family->getFamilyName());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new Family())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new Family())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'Family not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdMakesEntityIdentifiable(): void
    {
        $family = new Family();
        $family->setId(42);

        Assert::true($family->hasIdentity());
        Assert::same($family->reqId(), 42);
    }

    public function settersAndGetters(): void
    {
        $family = new Family(
            family_name: 'Electronics',
            family_commalist: 'tv,radio',
            family_productprefix: 'ELEC',
            category_primary_id: 1,
            category_secondary_id: 2,
            street_sort_order: 5,
        );

        Assert::same($family->getFamilyName(), 'Electronics');
        Assert::same($family->getFamilyCommalist(), 'tv,radio');
        Assert::same($family->getFamilyProductprefix(), 'ELEC');
        Assert::same($family->getCategoryPrimaryId(), 1);
        Assert::same($family->getCategorySecondaryId(), 2);
        Assert::same($family->getStreetSortOrder(), 5);
    }

    public function settersMutateValues(): void
    {
        $family = new Family();
        $family->setFamilyName('Tools');
        $family->setFamilyCommalist('hammer,drill');
        $family->setFamilyProductprefix('TOOL');
        $family->setCategoryPrimaryId(3);
        $family->setCategorySecondaryId(4);
        $family->setStreetSortOrder(10);

        Assert::same($family->getFamilyName(), 'Tools');
        Assert::same($family->getFamilyCommalist(), 'hammer,drill');
        Assert::same($family->getFamilyProductprefix(), 'TOOL');
        Assert::same($family->getCategoryPrimaryId(), 3);
        Assert::same($family->getCategorySecondaryId(), 4);
        Assert::same($family->getStreetSortOrder(), 10);
    }
}
