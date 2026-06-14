<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class SoUrlKeyDeps
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly SoAR $soaR,
        public readonly SoIR $soiR,
        public readonly SoIAR $soiaR,
        public readonly ACSOIR $acsoiR,
        public readonly SoR $soR,
        public readonly SoTRR $sotrR,
        public readonly UIR $uiR,
        public readonly UCR $ucR,
    ) {}
}
