<?php

declare(strict_types=1);

namespace App\Invoice\PurchaseEntry;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;

/**
 * Reduces an iterable of PurchaseEntry objects to VAT100 Box 4 / Box 7 totals.
 * Extracted from PurchaseEntryRepository so the summation logic can be unit-tested
 * without ORM infrastructure.
 */
final class PurchaseEntryVatAggregator
{
    /**
     * @param iterable<PurchaseEntry> $entries
     * @return array{input_vat: float, purchases_ex_vat: float}
     */
    public function aggregate(iterable $entries): array
    {
        $inputVat = 0.0;
        $purchasesExVat = 0.0;

        foreach ($entries as $entry) {
            $inputVat += $entry->getVatAmount();
            $purchasesExVat += $entry->getAmountExVat();
        }

        return [
            'input_vat'        => round($inputVat, 2),
            'purchases_ex_vat' => round($purchasesExVat, 2),
        ];
    }
}
