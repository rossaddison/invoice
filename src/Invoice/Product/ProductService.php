<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Product;
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
     * @return string
     */
    public function saveProduct(
        Product $model,
        array $array
    ): string {
        /**
         * @var array $array['ProductForm']
         */
        $apf = $array['ProductForm'];
        $this->persist($model, $apf);
        isset($apf['product_sku']) ? 
            $model->setProductSku(
                (string) $array['ProductForm']['product_sku']) : '';
        isset($apf['product_sii_schemeid']) ? 
            $model->setProductSiiSchemeid(
                (string) $apf['product_sii_schemeid']) : '';
        isset($apf['product_sii_id']) ? 
            $model->setProductSiiId(
                (string) $apf['product_sii_id']) : '';
        isset($apf['product_icc_listid']) ? 
            $model->setProductIccListid(
                (string) $apf['product_icc_listid']) : '';
        isset($apf['product_icc_listversionid']) ? 
            $model->setProductIccListversionid(
                (string) $apf['product_icc_listversionid']) : '';
        isset($apf['product_icc_id']) ? 
            $model->setProductIccId(
                (string) $apf['product_icc_id']) : '';
        isset($apf['product_country_of_origin_code']) ? 
            $model->setProductCountryOfOriginCode(
                (string) $apf['product_country_of_origin_code']) 
            : '';
        isset($apf['product_name']) ? 
            $model->setProductName(
                (string) $apf['product_name']) : '';
        isset($apf['product_description']) ? 
            $model->setProductDescription(
                (string) $apf['product_description']) : '';
        isset($apf['product_price']) ? 
            $model->setProductPrice(
                (float) $apf['product_price']) : '';
        isset($apf['product_price_base_quantity']) ? 
            $model->setProductPriceBaseQuantity(
                (float) $apf['product_price_base_quantity']) : '';
        isset($apf['purchase_price']) ? 
            $model->setPurchasePrice(
                (float) $apf['purchase_price']) : '';
        isset($apf['provider_name']) ? 
            $model->setProviderName(
                (string) $apf['provider_name']) : '';
        isset($apf['product_additional_item_property_name']) ? 
            $model->setProductAdditionalItemPropertyName(
                (string) $apf[
                    'product_additional_item_property_name']) : '';
        isset($apf['product_additional_item_property_value']) ? 
            $model->setProductAdditionalItemPropertyValue(
                (string) $apf[
                    'product_additional_item_property_value']) : '';
        isset($apf['tax_rate_id']) ? 
            $model->setTaxRateId(
                (int) $apf['tax_rate_id']) : '';
        isset($apf['unit_id']) ? 
            $model->setUnitId((int) $apf['unit_id']) : '';
        isset($apf['unit_peppol_id']) ? 
            $model->setUnitPeppolId(
                (int) $apf['unit_peppol_id']) : '';
        isset($apf['family_id']) ? 
            $model->setFamilyId((int) $apf['family_id']) : '';
        $this->repository->save($model);
        return $model->getProductId();
    }

    private function persist(
        Product $model,
        array $array
    ): Product {
        $family = 'family_id';
        if (isset($array[$family])) {
            $familyEntity = $this->fR->repoFamilyquery(
                (string) $array[$family]);
            if ($familyEntity) {
                $model->setFamily($familyEntity);
            }
        }
        $tax_rate = 'tax_rate_id';
        if (isset($array[$tax_rate])) {
            $model->setTaxrate(
                $this->trR->repoTaxRatequery(
                    (string) $array[$tax_rate]));
        }
        $unit = 'unit_id';
        if (isset($array[$unit])) {
            $unitEntity = $this->uR->repoUnitquery(
                (string) $array[$unit]);
            if ($unitEntity) {
                $model->setUnit($unitEntity);
            }
        }
        return $model;
    }

    /**
     * @param Product $model
     */
    public function deleteProduct(Product $model): void
    {
        $this->repository->delete($model);
    }
}
