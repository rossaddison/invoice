<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Upload\UploadRepository as UPR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class InvPdfDeps
{
    public function __construct(
        public readonly ACIR $aciR,
        public readonly ACIIR $aciiR,
        public readonly CFR $cfR,
        public readonly CR $cR,
        public readonly CVR $cvR,
        public readonly DLR $dlR,
        public readonly GR $gR,
        public readonly IAR $iaR,
        public readonly ICR $icR,
        public readonly IIAR $iiaR,
        public readonly IIR $iiR,
        public readonly InvRepository $iR,
        public readonly ITRR $itrR,
        public readonly SOR $soR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly UPR $upR,
    ) {
    }
}
