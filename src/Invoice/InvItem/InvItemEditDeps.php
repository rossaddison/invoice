<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Payment\PaymentRepository as PYMR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;

final class InvItemEditDeps
{
    public function __construct(
        public readonly InvItemRepository $iiR,
        public readonly TRR $trR,
        public readonly PYMR $pymR,
        public readonly PR $pR,
        public readonly UR $uR,
        public readonly IAR $iaR,
        public readonly IR $iR,
        public readonly IIAS $iias,
        public readonly IRR $irR,
        public readonly IIAR $iiaR,
        public readonly ITRR $itrR,
        public readonly ACIR $aciR,
        public readonly ACIIR $aciiR,
        public readonly TaskR $taskR,
    ) {
    }
}
