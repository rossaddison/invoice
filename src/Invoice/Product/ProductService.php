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
      $model->nullifyRelationOnChange((int)$array['ProductForm']['tax_rate_id'], (int)$array['ProductForm']['unit_id'], (int)$array['ProductForm']['family_id']);
      isset($array['ProductForm']['product_sku']) ? $model->setProduct_sku((string)$array['ProductForm']['product_sku']) : '';
      isset($array['ProductForm']['product_sii_schemeid']) ? $model->setProduct_sii_schemeid((string)$array['ProductForm']['product_sii_schemeid']) : '';
      isset($array['ProductForm']['product_sii_id']) ? $model->setProduct_sii_id((string)$array['ProductForm']['product_sii_id']) : '';
      isset($array['ProductForm']['product_icc_listid']) ?  $model->setProduct_icc_listid((string)$array['ProductForm']['product_icc_listid']) : '';
      isset($array['ProductForm']['product_icc_listversionid']) ?  $model->setProduct_icc_listversionid((string)$array['ProductForm']['product_icc_listversionid']) : '';
      isset($array['ProductForm']['product_icc_id']) ?  $model->setProduct_icc_id((string)$array['ProductForm']['product_icc_id']) : '';
      isset($array['ProductForm']['product_country_of_origin_code']) ? $model->setProduct_country_of_origin_code((string)$array['ProductForm']['product_country_of_origin_code']) : '';
      isset($array['ProductForm']['product_name']) ? $model->setProduct_name((string)$array['ProductForm']['product_name']): '';
      isset($array['ProductForm']['product_description']) ? $model->setProduct_description((string)$array['ProductForm']['product_description']): '';
      isset($array['ProductForm']['product_price']) ? $model->setProduct_price((float)$array['ProductForm']['product_price']): '';
      isset($array['ProductForm']['product_price_base_quantity']) ? $model->setProduct_price_base_quantity((float)$array['ProductForm']['product_price_base_quantity']) : '';
      isset($array['ProductForm']['purchase_price']) ? $model->setPurchase_price((float)$array['ProductForm']['purchase_price']): '';
      isset($array['ProductForm']['provider_name']) ? $model->setProvider_name((string)$array['ProductForm']['provider_name']): '';
      isset($array['ProductForm']['product_tariff']) ? $model->setProduct_tariff((float)$array['ProductForm']['product_tariff']): '';        
      isset($array['ProductForm']['product_additional_item_property_name']) ? $model->setProduct_additional_item_property_name((string)$array['ProductForm']['product_additional_item_property_name']): '';
      isset($array['ProductForm']['product_additional_item_property_value']) ? $model->setProduct_additional_item_property_value((string)$array['ProductForm']['product_additional_item_property_value']): '';
      isset($array['ProductForm']['tax_rate_id']) ? $model->setTax_rate_id((int)$array['ProductForm']['tax_rate_id']) : '';       
      isset($array['ProductForm']['unit_id']) ? $model->setUnit_id((int)$array['ProductForm']['unit_id']) : '';
      isset($array['ProductForm']['unit_peppol_id']) ? $model->setUnit_peppol_id((int)$array['ProductForm']['unit_peppol_id']) : '';
      isset($array['ProductForm']['family_id']) ? $model->setFamily_id((int)$array['ProductForm']['family_id']) : '';
      $this->repository->save($model);
      return $model->getProduct_id();
    }
    
    /**
     * @param Product $model
     * @param array $array
     * @return void
     */
    public function editProduct(Product $model, array $array): void
    {
        isset($array['ProductForm']['product_sku']) ? $model->setProduct_sku((string)$array['ProductForm']['product_sku']) : '';
        isset($array['ProductForm']['product_name']) ? $model->setProduct_name((string)$array['ProductForm']['product_name']) : '';
        isset($array['ProductForm']['product_description']) ? $model->setProduct_description((string)$array['ProductForm']['product_description']) : '';
        isset($array['ProductForm']['product_price']) ? $model->setProduct_price((float)$array['ProductForm']['product_price']) : '';
        isset($array['ProductForm']['product_price_base_quantity']) ? $model->setProduct_price_base_quantity((float)$array['ProductForm']['product_price_base_quantity']) : '';
        isset($array['ProductForm']['purchase_price']) ? $model->setPurchase_price((float)$array['ProductForm']['purchase_price']) : '';
        isset($array['ProductForm']['provider_name']) ? $model->setProvider_name((string)$array['ProductForm']['provider_name']) : '';
        isset($array['ProductForm']['product_tariff']) ? $model->setProduct_tariff((float)$array['ProductForm']['product_tariff']) : '';        
        isset($array['ProductForm']['unit_peppol_id']) ? $model->setUnit_peppol_id((int)$array['ProductForm']['unit_peppol_id']) : '';
        isset($array['ProductForm']['tax_rate_id']) 
        && $model->getTaxrate()?->getTax_rate_id() == $array['ProductForm']['tax_rate_id']
        ? $model->setTaxrate($model->getTaxrate()) : $model->setTaxrate(null);
        
        isset($array['ProductForm']['tax_rate_id']) ? $model->setTax_rate_id((int)$array['ProductForm']['tax_rate_id']) : '';       
        
        isset($array['ProductForm']['unit_id']) 
        && $model->getUnit()?->getUnit_id() == $array['ProductForm']['unit_id']
        ? $model->setUnit($model->getUnit()) : $model->setUnit(null);
        
        isset($array['ProductForm']['unit_id']) ? $model->setUnit_id((int)$array['ProductForm']['unit_id']) : '';
        
        isset( $array['ProductForm']['family_id'])
        && $model->getFamily()?->getFamily_id() == $array['ProductForm']['family_id']
        ? $model->setFamily($model->getFamily()) : $model->setFamily(null);
        
        isset( $array['ProductForm']['family_id']) ? $model->setFamily_id((int)$array['ProductForm']['family_id']) : '';
        
        $this->repository->save($model);
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
