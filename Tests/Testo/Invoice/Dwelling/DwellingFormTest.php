<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Dwelling;

use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Invoice\Dwelling\DwellingForm;
use Testo\Assert;
use Testo\Test;

/**
 * The dwelling form shows what the entity holds.
 */
#[Test]
final class DwellingFormTest
{
    public function theFormShowsEveryFieldOfTheDwelling(): void
    {
        $dwelling = new Dwelling(
            family_id: 5,
            house_number_numeric: 12,
            house_number_suffix: 'A',
            flat_unit: 'Flat 2',
            postcode: 'AB12CD',
            latitude: 55.5,
            longitude: -3.5,
            source: 'run_import',
        );
        $dwelling->setPullPoint(true);
        $at = new \DateTimeImmutable('2026-11-09 10:30:00');
        $dwelling->markDelegated($at);

        $form = DwellingForm::show($dwelling);

        Assert::same($form->getFamilyId(), 5);
        Assert::same($form->getHouseNumberNumeric(), 12);
        Assert::same($form->getHouseNumberSuffix(), 'A');
        Assert::same($form->getFlatUnit(), 'Flat 2');
        Assert::same($form->getPostcode(), 'AB12CD');
        Assert::same($form->getLatitude(), 55.5);
        Assert::same($form->getLongitude(), -3.5);
        Assert::same($form->getSource(), 'run_import');
        Assert::true($form->isPullPoint());
        Assert::same($form->getPullPointSortOrder(), $at);
        Assert::same($form->getFormName(), '');
    }

    public function aPlainDwellingIsNotAPullPointAndWasNeverDelegated(): void
    {
        $form = DwellingForm::show(new Dwelling(family_id: 5, house_number_numeric: 1, postcode: 'AB12CD'));

        Assert::false($form->isPullPoint());
        Assert::null($form->getPullPointSortOrder());
    }
}
