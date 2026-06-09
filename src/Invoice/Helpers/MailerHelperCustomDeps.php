<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;

final class MailerHelperCustomDeps
{
    public function __construct(
        public readonly CCR $ccR,
        public readonly QCR $qcR,
        public readonly ICR $icR,
        public readonly PCR $pcR,
        public readonly SOCR $socR,
        public readonly CFR $cfR,
        public readonly CVR $cvR,
    ) {
    }
}
