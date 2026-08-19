<?php

declare(strict_types=1);

namespace App\Api;

use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Group\GroupRepository as gR;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\Inv\InvService;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\InvItem\IiAddProductDeps;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as unR;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\User\UserRepository as uR;
use Yiisoft\FormModel\FormHydrator;

/**
 * Bundles OrderService's dependencies — matches this codebase's established
 * "Deps" bag convention (see e.g. HomeCareSignupConfirmDeps, IiAddProductDeps)
 * rather than a single sprawling constructor.
 */
final class OrderServiceDeps
{
    public function __construct(
        public readonly cR $cR,
        public readonly pR $pR,
        public readonly trR $trR,
        public readonly unR $unR,
        public readonly iR $iR,
        public readonly InvService $invService,
        public readonly gR $gR,
        public readonly iaR $iaR,
        public readonly iiR $iiR,
        public readonly InvItemService $invItemService,
        public readonly IiAddProductDeps $iiaDeps,
        public readonly NumberHelper $numberHelper,
        public readonly sR $sR,
        public readonly uR $uR,
        public readonly uiR $uiR,
        public readonly FormHydrator $formHydrator,
    ) {
    }
}
