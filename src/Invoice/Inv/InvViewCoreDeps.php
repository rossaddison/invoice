<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\Payment\PaymentRepository as PYMR;

final class InvViewCoreDeps
{
    public function __construct(
        public readonly InvRepository $iR,
        public readonly IAR $iaR,
        public readonly ICR $icR,
        public readonly IRR $irR,
        public readonly PYMR $pymR,
        public readonly GR $gR,
    ) {
    }
}
