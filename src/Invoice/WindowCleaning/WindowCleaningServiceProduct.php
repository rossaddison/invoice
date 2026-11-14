<?php

declare(strict_types=1);

namespace App\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\Family\Family;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Enum\ProductType;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Family\FamilyService;
use App\Invoice\Product\ProductNameTypeRepository;
use App\Invoice\Product\ProductService;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;

/**
 * Makes sure the one default service every window-cleaning invoice line uses is in the Product
 * catalogue: the shared "HomeCare Service" product (see ProductService), so it is there from the
 * start instead of appearing with the first sign up. Its price is 0.00 on purpose: the price of each
 * house lives on the invoice line.
 *
 * A Product needs a street (family), a tax rate and a unit. The first tax rate and unit are used.
 * With no street yet a placeholder one is put under the first run, so the product is not tied to a
 * real street; nothing ever looks the product up by that street.
 */
final readonly class WindowCleaningServiceProduct
{
    public const string PLACEHOLDER_STREET = 'Example Street';

    public function __construct(
        private ProductService $productService,
        private ProductNameTypeRepository $nameTypeRepository,
        private FamilyRepository $fR,
        private FamilyService $fS,
        private CategorySecondaryRepository $csR,
        private TaxRateRepository $trR,
        private UnitRepository $uR,
    ) {
    }

    /**
     * @return bool whether the service is in the catalogue afterwards; false when a tax rate, a unit
     *     or a run (to put a placeholder street under) is missing
     */
    public function ensure(): bool
    {
        if (null !== $this->nameTypeRepository->repoProductByNameAndTypeQuery(
            ProductService::HOMECARE_SERVICE_PRODUCT_NAME,
            ProductType::Service->value,
        )) {
            return true;
        }
        $taxRate = $this->trR->repoFirstByIdQuery();
        $unit = $this->uR->repoFirstByIdQuery();
        $familyId = $this->anyFamilyId();
        if (null === $taxRate || null === $unit || null === $familyId) {
            return false;
        }
        $this->productService->findOrCreateHomeCareServiceProduct($familyId, $taxRate->reqId(), $unit->reqId());
        return true;
    }

    private function anyFamilyId(): ?int
    {
        /** @var Family $family */
        foreach ($this->fR->findAllPreloaded() as $family) {
            return $family->reqId();
        }
        $runIds = array_keys($this->csR->optionsDataCategorySecondaries());
        if ([] === $runIds) {
            return null;
        }
        return $this->fS->findOrCreateByStreetName(self::PLACEHOLDER_STREET, (int) $runIds[0])->reqId();
    }
}
