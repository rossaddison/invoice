<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use DateTimeImmutable;

/**
 * Puts a run's houses under their pull points. In street order and then house-number order, a house
 * belongs to the nearest pull point before it, so a pull point heads the group of houses pulled
 * from it. The groups are then ordered by when each pull point was last delegated (earliest first);
 * a pull point not yet delegated follows, in street order. Houses that come before the run's first
 * pull point belong to no pull point and stay last, in street order.
 *
 * With no pull point in the run nothing moves, so it is safe to apply to any run sheet.
 */
final class PullPointGrouper
{
    /**
     * @param list<RunSheetPdfRow> $rows already in street order
     * @return list<RunSheetPdfRow>
     */
    public function group(array $rows): array
    {
        usort($rows, static fn (RunSheetPdfRow $a, RunSheetPdfRow $b): int => self::byStreetThenNumber($a, $b));

        /** @var list<list<RunSheetPdfRow>> $groups */
        $groups = [];
        /** @var list<RunSheetPdfRow> $unassigned */
        $unassigned = [];
        foreach ($rows as $row) {
            if ($row->pullPoint) {
                $groups[] = [$row];
            } elseif ([] === $groups) {
                $unassigned[] = $row;
            } else {
                $groups[array_key_last($groups)][] = $row;
            }
        }
        /** @var list<array{pos: int, at: DateTimeImmutable|null, rows: list<RunSheetPdfRow>}> $indexed */
        $indexed = [];
        foreach ($groups as $position => $members) {
            $indexed[] = ['pos' => $position, 'at' => $members[0]->delegatedAt, 'rows' => $members];
        }
        usort($indexed, static fn (array $a, array $b): int => match (true) {
            null !== $a['at'] && null !== $b['at'] => ($a['at'] <=> $b['at']) ?: ($a['pos'] <=> $b['pos']),
            null !== $a['at'] => -1,
            null !== $b['at'] => 1,
            default => $a['pos'] <=> $b['pos'],
        });

        $ordered = [];
        foreach ($indexed as $group) {
            foreach ($group['rows'] as $row) {
                $ordered[] = $row;
            }
        }
        return [...$ordered, ...$unassigned];
    }

    private static function byStreetThenNumber(RunSheetPdfRow $a, RunSheetPdfRow $b): int
    {
        if (null !== $a->streetSortOrder && null !== $b->streetSortOrder) {
            return ($a->streetSortOrder <=> $b->streetSortOrder) ?: ($a->houseNumber <=> $b->houseNumber);
        }
        return match (true) {
            null !== $a->streetSortOrder => -1,
            null !== $b->streetSortOrder => 1,
            default => $a->clientName <=> $b->clientName,
        };
    }
}
