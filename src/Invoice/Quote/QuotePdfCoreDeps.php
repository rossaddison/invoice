<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class QuotePdfCoreDeps
{
    public function __construct(
        public readonly QR $qR,
        public readonly QAR $qaR,
        public readonly GR $gR,
        public readonly UIR $uiR,
        public readonly QCR $qcR,
    ) {
    }
}
