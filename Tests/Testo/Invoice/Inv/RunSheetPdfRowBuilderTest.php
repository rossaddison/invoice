<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\RunSheetPdfRow;
use App\Invoice\Inv\RunSheetPdfRowBuilder;
use Testo\Assert;
use Testo\Test;

/**
 * Pure logic only -- no database, no HTTP -- see RunSheetPdfRowBuilder's
 * own docblock for why this is directly testable without the trait's
 * Request/WebViewRenderer/MpdfHelper wiring.
 */
#[Test]
final class RunSheetPdfRowBuilderTest
{
    public function dwellingRowUsesHouseNumberStreetAndPostcode(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $inv = $this->invWithNumber('INV-1');
        $dwelling = new Dwelling(
            house_number_numeric: 12,
            house_number_suffix: 'A',
            postcode: 'SW1A1AA',
        );
        $family = new Family(family_name: 'Elm Street', street_sort_order: 3);

        $row = $builder->buildDwellingRowWithFamily($inv, null, $dwelling, $family);

        Assert::same($row->address, '12A Elm Street, SW1A1AA');
        Assert::same($row->streetSortOrder, 3);
        Assert::true(str_contains(
            $row->mapsUrl ?? '',
            'destination=12A%20Elm%20Street%2C%20SW1A1AA',
        ));
        // A freshly constructed Inv always has a default InvAmount
        // (balance 0.00, set in Inv's own constructor) -- confirms
        // getInvAmount()->getBalance() is actually wired into the row.
        Assert::same($row->balance, 0.00);
    }

    public function dwellingRowPrefersLatitudeLongitudeWhenBothSet(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $inv = $this->invWithNumber('INV-2');
        $dwelling = new Dwelling(
            house_number_numeric: 5,
            postcode: 'AB1 2CD',
            latitude: 51.5074,
            longitude: -0.1278,
        );
        $family = new Family(family_name: 'Oak Avenue');

        $row = $builder->buildDwellingRowWithFamily($inv, null, $dwelling, $family);

        Assert::true(str_contains($row->mapsUrl ?? '', 'destination=51.5074%2C-0.1278'));
    }

    public function dwellingRowIgnoresPartialCoordinates(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $inv = $this->invWithNumber('INV-3');
        $dwelling = new Dwelling(
            house_number_numeric: 7,
            postcode: 'AB1 2CD',
            latitude: 51.5074,
            longitude: null,
        );
        $family = new Family(family_name: 'Pine Road');

        $row = $builder->buildDwellingRowWithFamily($inv, null, $dwelling, $family);

        Assert::false(str_contains($row->mapsUrl ?? '', '51.5074'));
        Assert::true(str_contains($row->mapsUrl ?? '', 'Pine%20Road'));
    }

    /**
     * Dwelling.house_number_numeric is a non-nullable int defaulting to 0
     * -- a real dwelling always displays at least "0" (getHouseNumberDisplay()
     * is never truly empty) -- so the address is never blank even with no
     * Family/street name, unlike the fallback row below (which genuinely can
     * be blank when the client has no address at all).
     */
    public function dwellingRowWithNoFamilyStillShowsHouseNumber(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $inv = $this->invWithNumber('INV-4');
        $dwelling = new Dwelling(house_number_numeric: 9);
        $client = new Client(client_name: 'Jane', client_surname: 'Doe');

        $row = $builder->buildDwellingRowWithFamily($inv, $client, $dwelling, null);

        Assert::same($row->address, '9');
        Assert::null($row->streetSortOrder);
    }

    public function fallbackRowUsesClientAddressLines(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $inv = $this->invWithNumber('INV-5');
        $client = new Client(
            client_name: 'John',
            client_surname: 'Smith',
            client_address_1: '221B Baker Street',
            client_address_2: 'Marylebone',
        );

        $row = $builder->buildFallbackRow($inv, $client);

        Assert::same($row->address, '221B Baker Street Marylebone');
        Assert::null($row->streetSortOrder);
        Assert::true($row->mapsUrl !== null);
    }

    public function fallbackRowHasNoMapsUrlWhenClientHasNoAddress(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $inv = $this->invWithNumber('INV-6');
        $client = new Client(client_name: 'No', client_surname: 'Address');

        $row = $builder->buildFallbackRow($inv, $client);

        Assert::same($row->address, 'No Address');
        Assert::null($row->mapsUrl);
    }

    public function compareRowsOrdersByStreetSortOrderAscending(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $second = $this->row(streetSortOrder: 2);
        $first  = $this->row(streetSortOrder: 1);

        Assert::true($builder->compareRows($first, $second) < 0);
        Assert::true($builder->compareRows($second, $first) > 0);
    }

    public function compareRowsPutsDwellingBackedRowsBeforeFallbackRows(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $dwellingBacked = $this->row(streetSortOrder: 5);
        $fallback = $this->row(streetSortOrder: null);

        Assert::true($builder->compareRows($dwellingBacked, $fallback) < 0);
        Assert::true($builder->compareRows($fallback, $dwellingBacked) > 0);
    }

    public function compareRowsSortsTwoFallbackRowsByClientName(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $alice = $this->row(streetSortOrder: null, clientName: 'Alice');
        $bob   = $this->row(streetSortOrder: null, clientName: 'Bob');

        Assert::true($builder->compareRows($alice, $bob) < 0);
    }

    public function clientDisplayNameTrimsAndJoinsNameAndSurname(): void
    {
        $builder = new RunSheetPdfRowBuilder();

        Assert::same(
            $builder->clientDisplayName(new Client(client_name: 'Ada')),
            'Ada',
        );
        Assert::same($builder->clientDisplayName(null), '');
    }

    public function clientPhonePrefersMobileOverLandline(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $client = new Client(client_mobile: '07700900000', client_phone: '02071234567');

        Assert::same($builder->clientPhone($client), '07700900000');
    }

    public function clientPhoneFallsBackToLandlineWhenNoMobile(): void
    {
        $builder = new RunSheetPdfRowBuilder();
        $client = new Client(client_phone: '02071234567');

        Assert::same($builder->clientPhone($client), '02071234567');
    }

    public function clientPhoneIsNullWhenClientHasNeither(): void
    {
        $builder = new RunSheetPdfRowBuilder();

        Assert::null($builder->clientPhone(new Client()));
        Assert::null($builder->clientPhone(null));
    }

    private function row(?int $streetSortOrder, string $clientName = ''): RunSheetPdfRow
    {
        return new RunSheetPdfRow(
            address: 'x',
            mapsUrl: null,
            clientName: $clientName,
            phone: null,
            invNumber: '',
            balance: null,
            streetSortOrder: $streetSortOrder,
        );
    }

    private function invWithNumber(string $number): Inv
    {
        $inv = new Inv();
        $inv->setNumber($number);
        return $inv;
    }
}
