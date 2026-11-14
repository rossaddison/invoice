<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Family\FamilyRepository;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

/**
 * Puts the invoices of the HomeCare inv/guest grid under their pull points, in the same order as
 * the run sheet PDF ({@see PullPointGrouper}). The order needs the street and house number of each
 * house, which a database sort cannot express, so the whole (already filtered) set is ordered in
 * PHP and handed to the grid as a reader.
 */
final readonly class GuestPullPointOrder
{
    public function __construct(
        private DwellingRepository $dwR,
        private FamilyRepository $fR,
        private RunSheetPdfRowBuilder $rowBuilder,
    ) {
    }

    /**
     * @param iterable<Inv> $invs
     * @return IterableDataReader<int<0, max>, Inv>
     */
    public function order(iterable $invs): IterableDataReader
    {
        /** @var array<int, Inv> $byId */
        $byId = [];
        foreach ($invs as $inv) {
            $byId[$inv->reqId()] = $inv;
        }
        $ordered = [];
        foreach ($this->rowBuilder->build($byId, $this->dwR, $this->fR) as $row) {
            if (isset($byId[$row->invId])) {
                $ordered[] = $byId[$row->invId];
            }
        }
        return new IterableDataReader($ordered);
    }
}
