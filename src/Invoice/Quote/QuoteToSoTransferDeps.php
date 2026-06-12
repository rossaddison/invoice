<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    SalesOrder\SalesOrderRepository as SOR,
    SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR,
    SalesOrderItemAmount\SalesOrderItemAmountRepository as soIAR,
    SalesOrderItemAmount\SalesOrderItemAmountService as soIAS,
};

final class QuoteToSoTransferDeps
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly SOR $soR,
        public readonly ACSOIR $acsoiR,
        public readonly soIAR $soiaR,
        public readonly soIAS $soiaS,
    ) {}
}
