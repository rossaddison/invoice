<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class QuoteEmailStage2Deps
{
    public function __construct(
        public readonly CCR $ccR,
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly GR $gR,
        public readonly IAR $iaR,
        public readonly ICR $icR,
        public readonly IR $iR,
        public readonly PCR $pcR,
        public readonly QAR $qaR,
        public readonly QCR $qcR,
        public readonly QR $qR,
        public readonly SOR $soR,
        public readonly SOCR $socR,
        public readonly UIR $uiR,
    ) {
    }
}
