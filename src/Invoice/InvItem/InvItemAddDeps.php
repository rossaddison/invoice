<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;

final class InvItemAddDeps
{
    public function __construct(
        public readonly PR $pR,
        public readonly UR $uR,
        public readonly TRR $trR,
        public readonly IRR $irR,
        public readonly IIAR $iiar,
        public readonly InvItemRepository $iiR,
        public readonly TaskR $taskR,
    ) {
    }
}
