<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItem;

use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use Yiisoft\FormModel\FormHydrator;

final class SalesOrderItemEditDeps
{
    public function __construct(
        public readonly FormHydrator $formHydrator,
        public readonly SOIR $soiR,
        public readonly TRR $trR,
        public readonly PR $pR,
        public readonly TaskR $taskR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly UR $uR,
        public readonly SOR $soR,
    ) {
    }
}
