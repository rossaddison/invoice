<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\ClientCustom\ClientCustomRepository as CcR;
use App\Invoice\CustomField\CustomFieldRepository as CfR;
use App\Invoice\CustomValue\CustomValueRepository as CvR;
use App\Invoice\InvAmount\InvAmountRepository as IaR;
use App\Invoice\InvCustom\InvCustomRepository as IcR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PcR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QaR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QcR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SocR;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\UserInv\UserInvRepository as UiR;

final class BodyPreviewDeps
{
    public function __construct(
        public readonly SR $sR,
        public readonly CcR $ccR,
        public readonly CfR $cfR,
        public readonly CvR $cvR,
        public readonly IaR $iaR,
        public readonly IcR $icR,
        public readonly IR $iR,
        public readonly PcR $pcR,
        public readonly QR $qR,
        public readonly QaR $qaR,
        public readonly QcR $qcR,
        public readonly SocR $socR,
        public readonly SoR $soR,
        public readonly UiR $uiR,
    ) {}
}
