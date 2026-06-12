<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvSentLog\InvSentLogRepository as ISLR;

final class InvFlushCoreDeps
{
    public function __construct(
        public readonly InvRepository $iR,
        public readonly ISLR $islR,
        public readonly IRR $irR,
        public readonly ICR $icR,
        public readonly IAR $iaR,
        public readonly IIR $iiR,
    ) {
    }
}
