<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Payment\PaymentRepository as PYMR;

final class CalcInvDeps
{
    public function __construct(
        public readonly ACIR $aciR,
        public readonly IIR $iiR,
        public readonly IIAR $iiaR,
        public readonly ITRR $itrR,
        public readonly IAR $iaR,
        public readonly IR $iR,
        public readonly PYMR $pymR,
    ) {}
}
