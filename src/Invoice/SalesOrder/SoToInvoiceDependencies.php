<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as InvRepo;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository as ACSOR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Task\TaskRepository as TASKR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;

final class SoToInvoiceDependencies
{
    public function __construct(
        public readonly ACIIR $aciiR,
        public readonly ACSOR $acsoR,
        public readonly ACSOIR $acsoiR,
        public readonly CFR $cfR,
        public readonly GR $gR,
        public readonly IIAR $iiaR,
        public readonly IIAS $iiaS,
        public readonly InvRepo $iR,
        public readonly PR $pR,
        public readonly SettingRepository $sR,
        public readonly SoCR $socR,
        public readonly SoIR $soiR,
        public readonly SalesOrderRepository $soR,
        public readonly SoTRR $sotrR,
        public readonly TASKR $taskR,
        public readonly TRR $trR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly UNR $unR,
        public readonly UR $uR,
    ) {
    }
}
