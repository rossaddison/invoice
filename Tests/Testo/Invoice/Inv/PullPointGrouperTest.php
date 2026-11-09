<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\PullPointGrouper;
use App\Invoice\Inv\RunSheetPdfRow;
use DateTimeImmutable;
use Testo\Assert;
use Testo\Test;

/**
 * Houses go under the nearest pull point before them; the groups run in the order Dan delegated
 * them, undelegated groups after in street order, and houses before the first pull point last.
 */
#[Test]
final class PullPointGrouperTest
{
    private function row(int $street, int $number, bool $pull = false, ?string $at = null): RunSheetPdfRow
    {
        return new RunSheetPdfRow(
            address: $number . ' Street ' . $street,
            mapsUrl: null,
            clientName: 'c',
            phone: null,
            invNumber: '',
            balance: null,
            streetSortOrder: $street,
            pullPoint: $pull,
            houseNumber: $number,
            delegatedAt: null === $at ? null : new DateTimeImmutable($at),
        );
    }

    /**
     * @param list<RunSheetPdfRow> $rows
     * @return list<string>
     */
    private function addresses(array $rows): array
    {
        return array_map(static fn (RunSheetPdfRow $r): string => $r->address, $rows);
    }

    public function aRunWithNoPullPointIsOnlyPutInStreetAndNumberOrder(): void
    {
        $rows = [$this->row(2, 1), $this->row(1, 9), $this->row(1, 3)];

        Assert::same($this->addresses((new PullPointGrouper())->group($rows)), ['3 Street 1', '9 Street 1', '1 Street 2']);
    }

    public function housesFollowTheNearestPullPointBeforeThem(): void
    {
        $rows = [
            $this->row(1, 1), $this->row(1, 2, true), $this->row(1, 3), $this->row(1, 4),
            $this->row(1, 5, true), $this->row(1, 6),
        ];

        // no delegation yet: street order, the leading house (no pull point) last
        Assert::same(
            $this->addresses((new PullPointGrouper())->group($rows)),
            ['2 Street 1', '3 Street 1', '4 Street 1', '5 Street 1', '6 Street 1', '1 Street 1'],
        );
    }

    public function groupsRunInTheOrderTheyWereDelegatedAndUndelegatedOnesFollow(): void
    {
        $rows = [
            $this->row(1, 1, true),                          // not delegated
            $this->row(1, 2),
            $this->row(1, 5, true, '2026-11-09 11:00:00'),   // delegated second
            $this->row(1, 6),
            $this->row(2, 1, true, '2026-11-09 09:30:00'),   // delegated first
            $this->row(2, 2),
        ];

        Assert::same(
            $this->addresses((new PullPointGrouper())->group($rows)),
            ['1 Street 2', '2 Street 2', '5 Street 1', '6 Street 1', '1 Street 1', '2 Street 1'],
        );
    }

    public function twoStreetsWithTheSameOrderStayContiguousInsteadOfInterleavingByHouseNumber(): void
    {
        $house = static fn (int $family, int $number, bool $pull = false): RunSheetPdfRow => new RunSheetPdfRow(
            address: $number . ' Street ' . $family,
            mapsUrl: null,
            clientName: 'c',
            phone: null,
            invNumber: '',
            balance: null,
            streetSortOrder: 1,
            pullPoint: $pull,
            houseNumber: $number,
            familyId: $family,
        );
        // both streets have order 1: street 2's houses come in between street 1's by number alone
        $rows = [$house(1, 1, true), $house(2, 2), $house(1, 3), $house(2, 4)];

        Assert::same(
            $this->addresses((new PullPointGrouper())->group($rows)),
            ['1 Street 1', '3 Street 1', '2 Street 2', '4 Street 2'],
        );
    }}
