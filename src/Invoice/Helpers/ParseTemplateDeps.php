<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\UserInv\UserInvRepository as uiR;

final class ParseTemplateDeps
{
    public function __construct(
        public readonly cvR $cvR,
        public readonly IR $iR,
        public readonly IAR $iaR,
        public readonly QR $qR,
        public readonly QAR $qaR,
        public readonly SOR $soR,
        public readonly uiR $uiR,
    ) {}
}
