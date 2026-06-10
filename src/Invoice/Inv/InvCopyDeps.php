<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Task\TaskRepository as TASKR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;

final class InvCopyDeps
{
    public function __construct(
        public readonly ACIR $aciR,
        public readonly ACIIR $aciiR,
        public readonly GR $gR,
        public readonly IAR $iaR,
        public readonly ICR $icR,
        public readonly IIAR $iiaR,
        public readonly IIAS $iiaS,
        public readonly IIR $iiR,
        public readonly InvRepository $iR,
        public readonly ITRR $itrR,
        public readonly PR $pR,
        public readonly TASKR $taskR,
        public readonly TRR $trR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly UNR $unR,
        public readonly UR $uR,
    ) {
    }
}
