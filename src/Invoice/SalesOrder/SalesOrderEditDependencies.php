<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as InvRepo;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;

final class SalesOrderEditDependencies
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly CR $clientRepo,
        public readonly CVR $cvR,
        public readonly DR $delRepo,
        public readonly GR $gR,
        public readonly InvRepo $invRepo,
        public readonly SettingRepository $settingRepository,
        public readonly SoCR $socR,
        public readonly SalesOrderRepository $soR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {
    }
}
