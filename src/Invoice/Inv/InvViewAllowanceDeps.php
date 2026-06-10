<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;

final class InvViewAllowanceDeps
{
    public function __construct(
        public readonly ACR $acR,
        public readonly ACIR $aciR,
        public readonly ACIIR $aciiR,
        public readonly ITRR $itrR,
        public readonly FR $fR,
    ) {
    }
}
