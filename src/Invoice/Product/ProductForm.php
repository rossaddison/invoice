<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use Yiisoft\Form\Field\Number;
use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ProductForm extends FormModel
{
    private ?string $product_sku = null;
    private ?string $product_sii_schemeid = null;
    private ?string $product_sii_id = null;
    private ?string $product_icc_listid = null;
    private ?string $product_icc_listversionid = null;
    private ?string $product_icc_id = null;
    private ?string $product_country_of_origin_code = null;
    private ?string $product_name = null;
    private ?string $product_description = null;
    private ?string $product_additional_item_property_name = null;
    private ?string $product_additional_item_property_value = null;
    private ?float $product_price = 0.00;
    private float $product_price_base_quantity = 1.00;
    private ?float $purchase_price = 0.00;
    private ?string $provider_name = null;
    
    // Get => string;  Set => int
    private ?string $family_id = '';
    
    // Get => string;  Set => int
    private ?string $tax_rate_id = '';
    
    // Get => string;  Set => int
    private ?string $unit_id = '';
    
    private ?string $unit_peppol_id = '';
    
    private ?int $product_tariff = null;
               
    public function getProduct_sku(): string|null
    {
        return $this->product_sku;
    }
    
    public function getProduct_sii_schemeid(): string|null
    {
        return $this->product_sii_schemeid;
    }
    
    public function getProduct_sii_id(): string|null
    {
        return $this->product_sii_id;
    }
    
    public function getProduct_icc_listid(): string|null
    {
        return $this->product_icc_listid;
    }
    
    public function getProduct_icc_listversionid(): string|null
    {
        return $this->product_icc_listversionid;
    }
    
    public function getProduct_icc_id(): string|null
    {
        return $this->product_icc_id;
    }
    
    public function getProduct_country_of_origin_code(): string|null
    {
        return $this->product_country_of_origin_code;
    }
    
    public function getProduct_name(): string|null
    {
        return $this->product_name;
    }
        
    public function getProduct_description(): string|null
    {
        return $this->product_description;
    }
    
    public function getProduct_price(): float|null
    {
        return $this->product_price;
    }
    
    public function getProduct_price_base_quantity(): float
    {
        return $this->product_price_base_quantity;
    }
    
    public function getPurchase_price(): float|null
    {
        return $this->purchase_price;
    }
    
    public function getProvider_name(): string|null
    {
        return $this->provider_name;
    }
    
    public function getProduct_additional_item_property_name(): string|null
    {
        return $this->product_additional_item_property_name;
    }
    
    public function getProduct_additional_item_property_value(): string|null
    {
        return $this->product_additional_item_property_value;
    }
    
    public function getFamily_id(): string|null
    {
        return $this->family_id;
    }
    
    public function getTax_rate_id(): string|null
    {
        return $this->tax_rate_id;
    }
    
    public function getUnit_id(): string|null
    {
        return $this->unit_id;
    }
    
    public function getUnit_peppol_id(): string|null
    {
        return $this->unit_peppol_id;
    }
    
    public function getProduct_tariff(): int|null
    {
        return $this->product_tariff;
    }
    
    /**
     * @return string
     *
     * @psalm-return 'ProductForm'
     */
    public function getFormName(): string
    {
        return 'ProductForm';
    }
    
    /**
     * 
     * @return array
     */
    public function getRules(): array
    {
        return [
          'family_id' => [new Required()],
          'product_name' => [new Required()],
          'product_price' => [(new Number())->min(0)->max(40)],
          'product_sku' => [new Required()],
          'tax_rate_id' => [new Required()],
          'unit_id' => [new Required()]
        ];
    }
}
