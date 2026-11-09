<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use App\Invoice\Client\ClientDwellingRepository;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Dwelling\DwellingService;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Family\FamilyService;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Helpers\InvRecalculator;
use App\Invoice\Inv\InvService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\InvItemAmount\InvItemAmountService;
use App\Invoice\Product\ProductRepository;
use App\Invoice\ProductClient\ProductClientService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;

/**
 * The collaborators {@see RunImportService} needs, bundled so the service stays within SonarQube
 * S107 (at most 7 parameters), the same way HomeCareSignupConfirmDeps does for the signup flow.
 */
final readonly class RunImportDeps
{
    public function __construct(
        public CategorySecondaryRepository $csR,
        public CategorySecondaryService $csService,
        public FamilyRepository $fR,
        public FamilyService $familyService,
        public DwellingService $dwellingService,
        public DwellingRepository $dwR,
        public ClientRepository $cR,
        public ClientDwellingRepository $cdR,
        public InvService $invService,
        public InvItemService $invItemService,
        public InvItemAmountRepository $iiaR,
        public InvItemAmountService $iias,
        public InvRecalculator $invRecalculator,
        public ProductRepository $pR,
        public ProductClientService $productClientService,
        public TaxRateRepository $trR,
        public UnitRepository $unR,
        public GroupRepository $gR,
        public SettingRepository $sR,
    ) {
    }
}
