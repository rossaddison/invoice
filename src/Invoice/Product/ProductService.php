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
     * @param ProductForm $form
     * @return string
     */
    public function saveProduct(Product $model, ProductForm $form): string
    {
      $model->nullifyRelationOnChange((int)$form->getTax_rate_id(), (int)$form->getUnit_id(), (int)$form->getFamily_id());
      null!==$form->getProduct_sku() ? $model->setProduct_sku($form->getProduct_sku()) : '';
      null!==$form->getProduct_sii_schemeid() ? $model->setProduct_sii_schemeid($form->getProduct_sii_schemeid()) : '';
      null!==$form->getProduct_sii_id() ? $model->setProduct_sii_id($form->getProduct_sii_id()) : '';
      null!==$form->getProduct_icc_listid() ? $model->setProduct_icc_listid($form->getProduct_icc_listid()) : '';
      null!==$form->getProduct_icc_listversionid() ? $model->setProduct_icc_listversionid($form->getProduct_icc_listversionid()) : '';
      null!==$form->getProduct_icc_id() ? $model->setProduct_icc_id($form->getProduct_icc_id()) : '';
      null!==$form->getProduct_country_of_origin_code() ? $model->setProduct_country_of_origin_code($form->getProduct_country_of_origin_code()) : '';
      null!==$form->getProduct_name() ? $model->setProduct_name($form->getProduct_name()): '';
      null!==$form->getProduct_description() ? $model->setProduct_description($form->getProduct_description()): '';
      null!==$form->getProduct_price() ? $model->setProduct_price($form->getProduct_price()): '';
      $model->setProduct_price_base_quantity($form->getProduct_price_base_quantity());
      null!==$form->getPurchase_price() ? $model->setPurchase_price($form->getPurchase_price()): '';
      null!==$form->getProvider_name() ? $model->setProvider_name($form->getProvider_name()): '';
      null!==$form->getProduct_tariff() ? $model->setProduct_tariff($form->getProduct_tariff()): '';        
      null!==$form->getProduct_additional_item_property_name() ? $model->setProduct_additional_item_property_name($form->getProduct_additional_item_property_name()): '';
      null!==$form->getProduct_additional_item_property_value() ? $model->setProduct_additional_item_property_value($form->getProduct_additional_item_property_value()): '';
      null!==$form->getTax_rate_id() ? $model->setTax_rate_id((int)$form->getTax_rate_id()) : '';       
      null!==$form->getUnit_id() ? $model->setUnit_id((int)$form->getUnit_id()) : '';
      null!==$form->getUnit_peppol_id() ? $model->setUnit_peppol_id((int)$form->getUnit_peppol_id()) : '';
      null!== $form->getFamily_id() ? $model->setFamily_id((int)$form->getFamily_id()) : '';
      $this->repository->save($model);
      return $model->getProduct_id();
    }
    
    /**
     * @param Product $model
     * @param ProductForm $form
     * @return void
     */
    public function editProduct(Product $model, ProductForm $form): void
    {
        null!==$form->getProduct_sku() ? $model->setProduct_sku($form->getProduct_sku()) : '';
        null!==$form->getProduct_name() ? $model->setProduct_name($form->getProduct_name()) : '';
        null!==$form->getProduct_description() ? $model->setProduct_description($form->getProduct_description()) : '';
        null!==$form->getProduct_price() ? $model->setProduct_price($form->getProduct_price()) : '';
        $model->setProduct_price_base_quantity($form->getProduct_price_base_quantity());
        null!==$form->getProvider_name() ? $model->setProvider_name($form->getProvider_name()) : '';
        null!==$form->getProvider_name() ? $model->setProvider_name($form->getProvider_name()) : '';
        null!==$form->getProduct_tariff() ? $model->setProduct_tariff($form->getProduct_tariff()) : '';        
        null!==$form->getUnit_peppol_id() ? $model->setUnit_peppol_id((int)$form->getUnit_peppol_id()) : '';
        null!==$form->getTax_rate_id() 
        && $model->getTaxrate()?->getTax_rate_id() == $form->getTax_rate_id()
        ? $model->setTaxrate($model->getTaxrate()) : $model->setTaxrate(null);
        
        null!==$form->getTax_rate_id() ? $model->setTax_rate_id((int)$form->getTax_rate_id()) : '';       
        
        null!==$form->getUnit_id() 
        && $model->getUnit()?->getUnit_id() == $form->getUnit_id()
        ? $model->setUnit($model->getUnit()) : $model->setUnit(null);
        
        null!==$form->getUnit_id() ? $model->setUnit_id((int)$form->getUnit_id()) : '';
        
        null!== $form->getFamily_id()
        && $model->getFamily()?->getFamily_id() == $form->getFamily_id()
        ? $model->setFamily($model->getFamily()) : $model->setFamily(null);
        
        null!== $form->getFamily_id() ? $model->setFamily_id((int)$form->getFamily_id()) : '';
        
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
