<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Enum\ProductType;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;

final readonly class ProductService
{
    public function __construct(
        private ProductRepository $repository,
        private FR $fR,
        private TRR $trR,
        private UR $uR,
    ) {
    }

    /**
     * @param Product $model
     * @param array $array
     */
    public function saveProduct(
        Product $model,
        array $array
    ): void {
        /**
         * @var array $array['ProductForm']
         */
        $apf = $array['ProductForm'];
        $this->persist($model, $apf);
        $this->applyProductCodeFields($model, $apf);
        $this->applyProductCoreFields($model, $apf);
        $this->applyProductReferenceIds($model, $apf);
        $this->repository->save($model);
    }

    private function applyProductCodeFields(Product $model, array $apf): void
    {
        isset($apf['product_sku']) ?
            $model->setProductSku((string) $apf['product_sku']) : '';
        isset($apf['product_sii_schemeid']) ?
            $model->setProductSiiSchemeid((string) $apf['product_sii_schemeid']) : '';
        isset($apf['product_sii_id']) ?
            $model->setProductSiiId((string) $apf['product_sii_id']) : '';
        isset($apf['product_icc_listid']) ?
            $model->setProductIccListid((string) $apf['product_icc_listid']) : '';
        isset($apf['product_icc_listversionid']) ?
            $model->setProductIccListversionid((string) $apf['product_icc_listversionid']) : '';
        isset($apf['product_icc_id']) ?
            $model->setProductIccId((string) $apf['product_icc_id']) : '';
        isset($apf['product_country_of_origin_code']) ?
            $model->setProductCountryOfOriginCode(
                (string) $apf['product_country_of_origin_code']) : '';
    }

    private function applyProductCoreFields(Product $model, array $apf): void
    {
        isset($apf['product_name']) ?
            $model->setProductName((string) $apf['product_name']) : '';
        isset($apf['product_description']) ?
            $model->setProductDescription((string) $apf['product_description']) : '';
        isset($apf['product_type']) ?
            $model->setProductType((string) $apf['product_type']) : '';
        isset($apf['product_price']) ?
            $model->setProductPrice((float) $apf['product_price']) : '';
        isset($apf['product_price_base_quantity']) ?
            $model->setProductPriceBaseQuantity(
                (float) $apf['product_price_base_quantity']) : '';
        isset($apf['purchase_price']) ?
            $model->setPurchasePrice((float) $apf['purchase_price']) : '';
        isset($apf['provider_name']) ?
            $model->setProviderName((string) $apf['provider_name']) : '';
        isset($apf['product_additional_item_property_name']) ?
            $model->setProductAdditionalItemPropertyName(
                (string) $apf['product_additional_item_property_name']) : '';
        isset($apf['product_additional_item_property_value']) ?
            $model->setProductAdditionalItemPropertyValue(
                (string) $apf['product_additional_item_property_value']) : '';
    }

    private function applyProductReferenceIds(Product $model, array $apf): void
    {
        isset($apf['tax_rate_id']) ?
            $model->setTaxRateId((int) $apf['tax_rate_id']) : '';
        isset($apf['unit_id']) ?
            $model->setUnitId((int) $apf['unit_id']) : '';
        isset($apf['unit_peppol_id']) ?
            $model->setUnitPeppolId((int) $apf['unit_peppol_id']) : '';
        isset($apf['family_id']) ?
            $model->setFamilyId((int) $apf['family_id']) : '';
    }

    private function persist(
        Product $model,
        array $array
    ): void {
        $family = 'family_id';
        if (isset($array[$family])) {
            $familyEntity = $this->fR->repoFamilyquery((int) $array[$family]);
            if ($familyEntity) {
                $model->setFamily($familyEntity);
            }
        }
        $tax_rate = 'tax_rate_id';
        if (isset($array[$tax_rate])) {
            $model->setTaxrate(
                $this->trR->repoTaxRatequery(
                    (int) $array[$tax_rate]));
        }
        $unit = 'unit_id';
        if (isset($array[$unit])) {
            $unitEntity = $this->uR->repoUnitquery(
                (int) $array[$unit]);
            if ($unitEntity) {
                $model->setUnit($unitEntity);
            }
        }
    }

    /**
     * @param Product $model
     */
    public function deleteProduct(Product $model): void
    {
        $this->repository->delete($model);
    }

    /**
     * Resolves the Service-type Product representing a single house number
     * under a street's Family (used by the HomeCare signup flow — one
     * Product per address, `product_type = Service`). Reuses the same
     * existence check as the staff-facing commalist bulk generator
     * (`FamilyController::createProductsFromCommalist()`). When no matching
     * Product exists yet, one is created at `$price` — an existing Product's
     * price/tax/unit are never overwritten by a later signup at the same
     * address.
     */
    public function findOrCreateHouseNumberProduct(
        int $familyId,
        string $buildingNumber,
        float $price,
        int $taxRateId,
        int $unitId,
    ): Product {
        /** @var Product $candidate */
        foreach ($this->repository->repoProductWithFamilyIdQuery($buildingNumber, $familyId) as $candidate) {
            return $candidate;
        }
        $product = new Product();
        $this->saveProduct($product, [
            'ProductForm' => [
                'product_name'        => $buildingNumber,
                'product_description' => $buildingNumber,
                'product_type'        => ProductType::Service->value,
                'product_price'       => $price,
                'family_id'           => (string) $familyId,
                'tax_rate_id'         => (string) $taxRateId,
                'unit_id'             => (string) $unitId,
            ],
        ]);
        return $product;
    }
}
