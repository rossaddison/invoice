<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class QuoteEmailStage0EntityDeps
{
    public function __construct(
        public readonly ETR $etR,
        public readonly QR $qR,
        public readonly SOCR $socR,
        public readonly UIR $uiR,
    ) {
    }
}
