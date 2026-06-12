<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;

final class InvPeppolChargeDeps
{
    public function __construct(
        public readonly ACIR $aciR,
        public readonly ACIIR $aciiR,
        public readonly SOIR $soiR,
        public readonly TRR $trR,
    ) {
    }
}
