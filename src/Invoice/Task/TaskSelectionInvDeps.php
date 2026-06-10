<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as itrR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\Payment\PaymentRepository as pymR;
use App\Invoice\TaxRate\TaxRateRepository as trR;

final class TaskSelectionInvDeps
{
    public function __construct(
        public readonly ACIR $aciR,
        public readonly iaR $iaR,
        public readonly iiaR $iiaR,
        public readonly iiR $iiR,
        public readonly iR $iR,
        public readonly itrR $itrR,
        public readonly pymR $pymR,
        public readonly TaskRepository $taskR,
        public readonly trR $trR,
    ) {
    }
}
