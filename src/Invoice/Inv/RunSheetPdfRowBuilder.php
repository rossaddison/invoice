<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Dwelling\DwellingRepository as DwR;
use App\Invoice\Family\FamilyRepository as FR;

/**
 * Pure row-building/sorting logic for Trait\RunSheetPdf, extracted into its
 * own stateless class so it's directly unit-testable without the HTTP/PDF
 * plumbing (Request, WebViewRenderer, MpdfHelper, ...) that trait also
 * needs -- see that trait's own docblock for the full address-source
 * reasoning (Inv -> Client -> Dwelling -> Family, not the
 * InvItem -> Product -> Family chain) and why non-dwelling rows fall back
 * to Client::getClientAddress1()/getClientAddress2().
 */
final class RunSheetPdfRowBuilder
{
    /**
     * @return list<RunSheetPdfRow>
     */
    public function build(iterable $invs, DwR $dwR, FR $fR): array
    {
        $rows = [];
        /** @var Inv $inv */
        foreach ($invs as $inv) {
            $rows[] = $this->buildRow($inv, $dwR, $fR);
        }
        usort($rows, [$this, 'compareRows']);
        return $rows;
    }

    public function compareRows(RunSheetPdfRow $a, RunSheetPdfRow $b): int
    {
        if ($a->streetSortOrder !== null && $b->streetSortOrder !== null) {
            return $a->streetSortOrder <=> $b->streetSortOrder;
        }
        // Dwelling-backed rows (a real street_sort_order) always sort
        // before rows with none (non-HomeCare clients, or a dwelling
        // whose street was never manually ordered).
        return match (true) {
            $a->streetSortOrder !== null => -1,
            $b->streetSortOrder !== null => 1,
            default                      => $a->clientName <=> $b->clientName,
        };
    }

    public function buildRow(Inv $inv, DwR $dwR, FR $fR): RunSheetPdfRow
    {
        $client = $inv->getClient();
        $dwellingId = $inv->getClientDwellingId();
        $dwelling = $dwellingId !== null
            ? $dwR->repoDwellingQuery($dwellingId)
            : null;

        return $dwelling !== null
            ? $this->buildDwellingRow($inv, $client, $dwelling, $fR)
            : $this->buildFallbackRow($inv, $client);
    }

    public function buildDwellingRow(
        Inv $inv,
        ?Client $client,
        Dwelling $dwelling,
        FR $fR,
    ): RunSheetPdfRow {
        $familyId = $dwelling->getFamilyId();
        $family   = $familyId !== null ? $fR->repoFamilyquery($familyId) : null;
        return $this->buildDwellingRowWithFamily($inv, $client, $dwelling, $family);
    }

    /**
     * Split out from buildDwellingRow() purely so a test can supply a
     * Family directly (a plain constructor, unlike FR, which needs a
     * database-backed Select) -- FamilyRepository::repoFamilyquery() is
     * a thin id-to-entity lookup with nothing of its own worth exercising
     * here.
     */
    public function buildDwellingRowWithFamily(
        Inv $inv,
        ?Client $client,
        Dwelling $dwelling,
        ?Family $family,
    ): RunSheetPdfRow {
        $streetName = $family?->getFamilyName() ?? '';
        $addressLine = trim(
            $dwelling->getHouseNumberDisplay() . ' ' . $streetName,
        );
        $postcode = $dwelling->getPostcode();
        $displayAddress = trim(
            $addressLine . ($postcode !== '' ? ', ' . $postcode : ''),
        );

        $latitude  = $dwelling->getLatitude();
        $longitude = $dwelling->getLongitude();
        $destination = ($latitude !== null && $longitude !== null)
            ? $latitude . ',' . $longitude
            : $displayAddress;

        $clientName = $this->clientDisplayName($client);
        return new RunSheetPdfRow(
            address: $displayAddress !== '' ? $displayAddress : $clientName,
            mapsUrl: $this->mapsUrl($destination),
            clientName: $clientName,
            phone: $this->clientPhone($client),
            invNumber: $inv->getNumber() ?? '',
            balance: $inv->getInvAmount()->getBalance(),
            streetSortOrder: $family?->getStreetSortOrder(),
        );
    }

    public function buildFallbackRow(Inv $inv, ?Client $client): RunSheetPdfRow
    {
        $address1 = $client?->getClientAddress1() ?? '';
        $address2 = $client?->getClientAddress2() ?? '';
        $addressLine = trim($address1 . ' ' . $address2);
        $clientName = $this->clientDisplayName($client);

        return new RunSheetPdfRow(
            address: $addressLine !== '' ? $addressLine : $clientName,
            mapsUrl: $addressLine !== ''
                ? $this->mapsUrl($addressLine)
                : null,
            clientName: $clientName,
            phone: $this->clientPhone($client),
            invNumber: $inv->getNumber() ?? '',
            balance: $inv->getInvAmount()->getBalance(),
            streetSortOrder: null,
        );
    }

    public function clientDisplayName(?Client $client): string
    {
        $name = $client?->getClientName() ?? '';
        $surname = $client?->getClientSurname() ?? '';
        return trim($name . ' ' . $surname);
    }

    /**
     * Prefers the mobile number (more useful in the field than a
     * landline -- a worker can call or text it from the door), falling
     * back to the landline when no mobile is on file. Null, not '',
     * when the client has neither -- lets the view render a plain dash
     * rather than an empty cell.
     */
    public function clientPhone(?Client $client): ?string
    {
        $mobile = $client?->getClientMobile() ?? '';
        if ($mobile !== '') {
            return $mobile;
        }
        $phone = $client?->getClientPhone() ?? '';
        return $phone !== '' ? $phone : null;
    }

    /**
     * A single-destination Google Maps navigation link -- deliberately not
     * a multi-stop route: Google Maps' own plain URL scheme caps at 10
     * total stops (origin + 9 waypoints), while a sequence of these
     * single-destination links has no such limit and defaults to the
     * device's current location as the origin (confirmed against Google's
     * own Maps URLs documentation), matching every stop's own turn-by-turn
     * navigation as the worker completes each visit in street_sort_order.
     */
    public function mapsUrl(string $destination): string
    {
        return 'https://www.google.com/maps/dir/?api=1&destination='
            . rawurlencode($destination) . '&travelmode=driving';
    }
}
