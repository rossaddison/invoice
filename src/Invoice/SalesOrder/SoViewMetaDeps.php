<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as InvRepo;
use App\Invoice\Setting\SettingRepository;

final class SoViewMetaDeps
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly CVR $cvR,
        public readonly GR $gR,
        public readonly InvRepo $invRepo,
        public readonly SettingRepository $settingRepository,
    ) {
    }
}
