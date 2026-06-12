<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\PaymentInformation\Service\BacsPaymentService;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class InvGuestDeps
{
    public function __construct(
        public readonly IAR $iaR,
        public readonly IRR $irR,
        public readonly InvRepository $iR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly BacsPaymentService $bacsPaymentService,
    ) {
    }
}
