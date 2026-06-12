<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;

final class InvUrlKeyRepoDeps
{
    public function __construct(
        public readonly InvRepository $iR,
        public readonly IAR $iaR,
        public readonly IIAR $iiaR,
        public readonly IIR $iiR,
        public readonly ITRR $itrR,
        public readonly CFR $cfR,
    ) {
    }
}
