<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class InvEmailStage0Deps
{
    public function __construct(
        public readonly CCR $ccR,
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly ETR $etR,
        public readonly ICR $icR,
        public readonly InvRepository $iR,
        public readonly PCR $pcR,
        public readonly QCR $qcR,
        public readonly SOCR $socR,
        public readonly UIR $uiR,
    ) {
    }
}
