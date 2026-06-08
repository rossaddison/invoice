<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Payment\PaymentRepository as PYMR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\Project\ProjectRepository as PRJCTR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Task\TaskRepository as TASKR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\Invoice\Upload\UploadRepository as UPR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class InvViewDeps
{
    public function __construct(
        public readonly ACR $acR,
        public readonly ACIR $aciR,
        public readonly ACIIR $aciiR,
        public readonly CFR $cfR,
        public readonly CR $cR,
        public readonly CVR $cvR,
        public readonly DLR $dlR,
        public readonly ETR $etR,
        public readonly FR $fR,
        public readonly GR $gR,
        public readonly IAR $iaR,
        public readonly ICR $icR,
        public readonly IIAR $iiaR,
        public readonly IIR $iiR,
        public readonly InvRepository $iR,
        public readonly IRR $irR,
        public readonly ITRR $itrR,
        public readonly PIR $piR,
        public readonly PMR $pmR,
        public readonly PR $pR,
        public readonly PRJCTR $prjctR,
        public readonly PYMR $pymR,
        public readonly SOR $soR,
        public readonly TASKR $taskR,
        public readonly TRR $trR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly UNR $unR,
        public readonly UPR $upR,
    ) {
    }
}
