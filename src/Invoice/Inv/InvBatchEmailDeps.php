<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class InvBatchEmailDeps
{
    public function __construct(
        public readonly InvRepository $iR,
        public readonly IAR $iaR,
        public readonly ICR $icR,
        public readonly ISLR $islR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly InvEmailCustomDeps $custom,
        public readonly InvEmailRelationDeps $relation,
    ) {}
}
