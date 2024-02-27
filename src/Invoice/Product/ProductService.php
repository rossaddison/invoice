<?php
declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Product;

final class ProductService
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * @param Product $model
     * @param array $array
     * @return string
     */
    public function saveProduct(Product $model, array $array): string
    {
        /**
         * @var array $array['ProductForm']
         */
        $apf = $array['ProductForm'];
        $model->nullifyRelationOnChange((int)$apf['tax_rate_id'], (int)$apf['unit_id'], (int)$apf['family_id']);
        isset($apf['product_sku']) ? $model->setProduct_sku((string)$array['ProductForm']['product_sku']) : '';
        isset($apf['product_sii_schemeid']) ? $model->setProduct_sii_schemeid((string)$apf['product_sii_schemeid']) : '';
        isset($apf['product_sii_id']) ? $model->setProduct_sii_id((string)$apf['product_sii_id']) : '';
        isset($apf['product_icc_listid']) ?  $model->setProduct_icc_listid((string)$apf['product_icc_listid']) : '';
        isset($apf['product_icc_listversionid']) ?  $model->setProduct_icc_listversionid((string)$apf['product_icc_listversionid']) : '';
        isset($apf['product_icc_id']) ?  $model->setProduct_icc_id((string)$apf['product_icc_id']) : '';
        isset($apf['product_country_of_origin_code']) ? $model->setProduct_country_of_origin_code((string)$apf['product_country_of_origin_code']) : '';
        isset($apf['product_name']) ? $model->setProduct_name((string)$apf['product_name']): '';
        isset($apf['product_description']) ? $model->setProduct_description((string)$apf['product_description']): '';
        isset($apf['product_price']) ? $model->setProduct_price((float)$apf['product_price']): '';
        isset($apf['product_price_base_quantity']) ? $model->setProduct_price_base_quantity((float)$apf['product_price_base_quantity']) : '';
        isset($apf['purchase_price']) ? $model->setPurchase_price((float)$apf['purchase_price']): '';
        isset($apf['provider_name']) ? $model->setProvider_name((string)$apf['provider_name']): '';
        isset($apf['product_tariff']) ? $model->setProduct_tariff((float)$apf['product_tariff']): '';        
        isset($apf['product_additional_item_property_name']) ? $model->setProduct_additional_item_property_name((string)$apf['product_additional_item_property_name']): '';
        isset($apf['product_additional_item_property_value']) ? $model->setProduct_additional_item_property_value((string)$apf['product_additional_item_property_value']): '';
        isset($apf['tax_rate_id']) ? $model->setTax_rate_id((int)$apf['tax_rate_id']) : '';       
        isset($apf['unit_id']) ? $model->setUnit_id((int)$apf['unit_id']) : '';
        isset($apf['unit_peppol_id']) ? $model->setUnit_peppol_id((int)$apf['unit_peppol_id']) : '';
        isset($apf['family_id']) ? $model->setFamily_id((int)$apf['family_id']) : '';
        $this->repository->save($model);
        return $model->getProduct_id();
    }
    
    /**
     * @param Product $model
     * @return void
     */
    public function deleteProduct(Product $model): void
    {
        $this->repository->delete($model);
    }
}
