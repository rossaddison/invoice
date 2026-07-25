<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\TokenRepository as tR;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as cpR;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Client\ClientService;
use App\Invoice\Family\FamilyRepository as fR;
use App\Invoice\Family\FamilyService;
use App\Invoice\HomeCarePendingSignup\HomeCarePendingSignupRepository as pendingR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\Inv\InvService;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\InvItemAmount\InvItemAmountService as iias;
use App\Invoice\Group\GroupRepository as gR;
use App\Invoice\Helpers\InvRecalculator;
use App\Invoice\Payment\PaymentService;
use App\Invoice\PaymentMethod\PaymentMethodRepository as pmR;
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\Product\ProductService;
use App\Invoice\ProductClient\ProductClientService;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as unR;
use App\Invoice\UserClient\UserClientRepository as ucR;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Invoice\UserInv\UserRbacLinkRepository as urlR;
use App\User\UserRepository as uR;

final class HomeCareSignupConfirmDeps
{
    public function __construct(
        public readonly tR $tR,
        public readonly uiR $uiR,
        public readonly uR $uR,
        public readonly cR $cR,
        public readonly ucR $ucR,
        public readonly urlR $urlR,
        public readonly ClientService $clientService,
        public readonly fR $fR,
        public readonly FamilyService $familyService,
        public readonly cpR $cpR,
        public readonly CategorySecondaryService $categorySecondaryService,
        public readonly pR $pR,
        public readonly ProductService $productService,
        public readonly trR $trR,
        public readonly unR $unR,
        public readonly iR $iR,
        public readonly InvService $invService,
        public readonly gR $gR,
        public readonly iiR $iiR,
        public readonly InvItemService $invItemService,
        public readonly iiaR $iiaR,
        public readonly iias $iias,
        public readonly InvRecalculator $invRecalculator,
        public readonly PaymentService $paymentService,
        public readonly pmR $pmR,
        public readonly ProductClientService $productClientService,
        public readonly sR $sR,
        public readonly pendingR $pendingR,
    ) {
    }
}
