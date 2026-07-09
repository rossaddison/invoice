<?php

declare(strict_types=1);

namespace App\Invoice\InvAmount;

use App\Infrastructure\Persistence\InvAmount\InvAmount;

/**
 * Reduces an iterable of InvAmount objects to VAT100 Box 1 / Box 6 totals.
 * Extracted from InvAmountRepository so the summation logic can be unit-tested
 * without ORM infrastructure.
 */
final class InvAmountVatAggregator
{
    /**
     * @param iterable<InvAmount> $invAmounts
     * @return array{output_vat: float, sales_ex_vat: float}
     */
    public function aggregate(iterable $invAmounts): array
    {
        $outputVat  = 0.0;
        $salesExVat = 0.0;

        foreach ($invAmounts as $invAmount) {
            $outputVat  += (float) $invAmount->getTaxTotal();
            $salesExVat += $invAmount->getItemSubtotal();
        }

        return [
            'output_vat'   => round($outputVat, 2),
            'sales_ex_vat' => round($salesExVat, 2),
        ];
    }
}
