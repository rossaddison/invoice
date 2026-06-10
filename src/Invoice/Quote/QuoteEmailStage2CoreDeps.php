<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class QuoteEmailStage2CoreDeps
{
    public function __construct(
        public readonly GR $gR,
        public readonly IAR $iaR,
        public readonly IR $iR,
        public readonly SOCR $socR,
        public readonly UIR $uiR,
    ) {
    }
}
