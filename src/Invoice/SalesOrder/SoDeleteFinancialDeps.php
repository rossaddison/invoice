<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountService as SoAS;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService as SoTRS;

final class SoDeleteFinancialDeps
{
    public function __construct(
        public readonly SoTRR $sotrR,
        public readonly SoTRS $sotrS,
        public readonly SoAR $soaR,
        public readonly SoAS $soaS,
    ) {}
}
