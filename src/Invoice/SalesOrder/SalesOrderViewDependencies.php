<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as InvRepo;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository as ACSOR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository as ACSOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SoIAR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Task\TaskRepository as TASKR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class SalesOrderViewDependencies
{
    public function __construct(
        public readonly ACSOIR $acsoiR,
        public readonly ACSOR $acsoR,
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly CR $cR,
        public readonly DR $dR,
        public readonly GR $gR,
        public readonly InvRepo $invRepo,
        public readonly PIR $piR,
        public readonly PR $pR,
        public readonly QR $qR,
        public readonly SettingRepository $settingRepository,
        public readonly SoAR $soaR,
        public readonly SoCR $socR,
        public readonly SoIAR $soiaR,
        public readonly SoIR $soiR,
        public readonly SalesOrderRepository $soR,
        public readonly SoTRR $sotrR,
        public readonly TASKR $taskR,
        public readonly TRR $trR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
        public readonly UNR $uR,
    ) {
    }
}
