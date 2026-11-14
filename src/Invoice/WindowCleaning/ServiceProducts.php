<?php

declare(strict_types=1);

namespace App\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\Family\Family;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Enum\ProductType;
use App\Invoice\Enum\ServiceType;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Family\FamilyService;
use App\Invoice\Product\ProductNameTypeRepository;
use App\Invoice\Product\ProductService;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;

/**
 * Makes sure the service products of a home-services business are in the Product catalogue: the shared
 * "HomeCare Service" product (see ProductService) and one product for each service the business offers
 * (see ServiceType), so they are there from the start instead of appearing with the first sign up. The
 * price is 0.00 on purpose: the price of each house lives on the invoice line.
 *
 * A Product needs a street (family), a tax rate and a unit. The first tax rate and unit are used.
 * With no street yet a placeholder one is put under the first run, so a product is not tied to a real
 * street; nothing ever looks a product up by that street. A product already in the catalogue is left
 * alone.
 */
final readonly class ServiceProducts
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
     * @param list<ServiceType> $services the services the business offers
     * @return bool whether every product is in the catalogue afterwards; false when a tax rate, a unit
     *     or a run (to put a placeholder street under) is missing
     */
    public function ensure(array $services = []): bool
    {
        $missing = $this->missingNames($services);
        if ([] === $missing) {
            return true;
        }
        $taxRate = $this->trR->repoFirstByIdQuery();
        $unit = $this->uR->repoFirstByIdQuery();
        $familyId = $this->anyFamilyId();
        if (null === $taxRate || null === $unit || null === $familyId) {
            return false;
        }
        foreach ($missing as $name) {
            $this->productService->findOrCreateServiceProduct($name, $familyId, $taxRate->reqId(), $unit->reqId());
        }
        return true;
    }

    /**
     * @param list<ServiceType> $services
     * @return list<string> the names of the products not yet in the catalogue
     */
    private function missingNames(array $services): array
    {
        $names = [ProductService::HOMECARE_SERVICE_PRODUCT_NAME];
        foreach ($services as $service) {
            $names[] = $service->productName();
        }
        $missing = [];
        foreach ($names as $name) {
            if (null === $this->nameTypeRepository->repoProductByNameAndTypeQuery($name, ProductType::Service->value)) {
                $missing[] = $name;
            }
        }
        return $missing;
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
