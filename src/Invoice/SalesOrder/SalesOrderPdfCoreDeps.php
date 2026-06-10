<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class SalesOrderPdfCoreDeps
{
    public function __construct(
        public readonly SoR $soR,
        public readonly SoAR $soaR,
        public readonly SoCR $socR,
        public readonly UIR $uiR,
    ) {
    }
}
