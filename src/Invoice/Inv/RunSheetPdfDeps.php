<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\InvWorkerAllocation\AllocationSidesSummary;

/**
 * What the staff run sheet PDF needs beyond the request, the filter and the index dependencies:
 * grouped so the action keeps to a short parameter list.
 */
final readonly class RunSheetPdfDeps
{
    public function __construct(
        public FR $fR,
        public RunSheetPdfRowBuilder $rowBuilder,
        public AllocationSidesSummary $sides,
    ) {
    }
}
