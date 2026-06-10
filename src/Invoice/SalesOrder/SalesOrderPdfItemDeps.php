<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;

final class SalesOrderPdfItemDeps
{
    public function __construct(
        public readonly SoIR $soiR,
        public readonly SoIAR $soiaR,
        public readonly ACSOIR $acsoiR,
        public readonly SoTRR $sotrR,
    ) {
    }
}
