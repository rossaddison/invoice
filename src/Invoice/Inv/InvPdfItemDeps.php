<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;

final class InvPdfItemDeps
{
    public function __construct(
        public readonly ACIR $aciR,
        public readonly IIR $iiR,
        public readonly ACIIR $aciiR,
        public readonly IIAR $iiaR,
        public readonly ITRR $itrR,
    ) {
    }
}
