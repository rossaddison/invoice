<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\SalesOrder\SalesOrderService;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeService;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService;
use App\Invoice\SalesOrderItem\SalesOrderItemService;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService;

final class QuoteControllerSoDeps
{
    public function __construct(
        public readonly SalesOrderAllowanceChargeService $soacService,
        public readonly SalesOrderCustomService $soCustomService,
        public readonly SalesOrderItemService $soItemService,
        public readonly SalesOrderService $soService,
        public readonly SalesOrderTaxRateService $soTaxRateService,
    ) {
    }
}
