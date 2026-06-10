<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class QuoteEmailStage0Deps
{
    public function __construct(
        public readonly CCR $ccR,
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly ETR $etR,
        public readonly ICR $icR,
        public readonly PCR $pcR,
        public readonly QR $qR,
        public readonly QCR $qcR,
        public readonly SOCR $socR,
        public readonly UIR $uiR,
    ) {
    }
}
