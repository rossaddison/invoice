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
use App\Invoice\Setting\SettingRepository as sR;

final class InvRecalculator
{
    public function __construct(
        private readonly ACIR $aciR,
        private readonly IAR $iaR,
        private readonly IR $iR,
        private readonly IIR $iiR,
        private readonly IIAR $iiaR,
        private readonly ITRR $itrR,
        private readonly PYMR $pmtR,
        private readonly sR $sR,
    ) {
    }

    public function recalculate(int $invId): void
    {
        (new NumberHelper($this->sR))->calculateInv(
            $invId,
            $this->aciR,
            $this->iiR,
            $this->iiaR,
            $this->itrR,
            $this->iaR,
            $this->iR,
            $this->pmtR,
        );
    }
}
