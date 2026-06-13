<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Payment\PaymentRepository as PYMR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;

final class InvFlushItemDeps
{
    public function __construct(
        public readonly IIAR $iiaR,
        public readonly ITRR $itrR,
        public readonly ACIIR $aciiR,
        public readonly ACIR $aciR,
        public readonly PCR $pcR,
        public readonly PYMR $pymR,
    ) {
    }
}
