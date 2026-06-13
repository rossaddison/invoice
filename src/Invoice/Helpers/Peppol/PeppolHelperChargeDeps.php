<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\{
    InvAllowanceCharge\InvAllowanceChargeRepository as ACIR,
    InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR,
    SalesOrderItem\SalesOrderItemRepository as SOIR,
    TaxRate\TaxRateRepository as TRR,
};

final class PeppolHelperChargeDeps
{
    public function __construct(
        public readonly ACIR $aciR,
        public readonly ACIIR $aciiR,
        public readonly SOIR $soiR,
        public readonly TRR $trR,
    ) {}
}
