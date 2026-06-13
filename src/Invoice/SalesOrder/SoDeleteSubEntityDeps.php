<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService as SoCS;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderItem\SalesOrderItemService as SoIS;

final class SoDeleteSubEntityDeps
{
    public function __construct(
        public readonly SoR $soR,
        public readonly SoCR $socR,
        public readonly SoCS $socS,
        public readonly SoIR $soiR,
        public readonly SoIS $soiS,
    ) {}
}
