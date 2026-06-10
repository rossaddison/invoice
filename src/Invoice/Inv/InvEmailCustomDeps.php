<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;

final class InvEmailCustomDeps
{
    public function __construct(
        public readonly CCR $ccR,
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly PCR $pcR,
        public readonly QCR $qcR,
        public readonly SOCR $socR,
    ) {
    }
}
