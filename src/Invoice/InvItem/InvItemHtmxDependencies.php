<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Payment\PaymentRepository as PYMR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;

final class InvItemHtmxDependencies
{
    public function __construct(
        public readonly ACIR $aciR,
        public readonly ACIIR $aciiR,
        public readonly IAR $iaR,
        public readonly IIAR $iiaR,
        public readonly InvItemRepository $iiR,
        public readonly IR $iR,
        public readonly ITRR $itrR,
        public readonly PIR $piR,
        public readonly PR $pR,
        public readonly PYMR $pymR,
        public readonly TaskR $taskR,
        public readonly TRR $trR,
        public readonly UR $uR,
    ) {
    }
}
