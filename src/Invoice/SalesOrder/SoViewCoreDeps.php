<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;

final class SoViewCoreDeps
{
    public function __construct(
        public readonly SalesOrderRepository $soR,
        public readonly SoAR $soaR,
        public readonly SoIR $soiR,
        public readonly SoTRR $sotrR,
        public readonly SoCR $socR,
        public readonly SoIAR $soiaR,
    ) {
    }
}
