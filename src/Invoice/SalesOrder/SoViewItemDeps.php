<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\Task\TaskRepository as TASKR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;

final class SoViewItemDeps
{
    public function __construct(
        public readonly PIR $piR,
        public readonly PR $pR,
        public readonly TASKR $taskR,
        public readonly TRR $trR,
        public readonly UNR $uR,
    ) {
    }
}
