<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use Yiisoft\Form\Field\Number;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ProductForm extends FormModel
{
    public ?string $product_sku = null;
    public ?string $product_sii_schemeid = null;
    public ?string $product_sii_id = null;
    public ?string $product_icc_listid = null;
    public ?string $product_icc_listversionid = null;
    public ?string $product_icc_id = null;
    public ?string $product_country_of_origin_code = null;
    public ?string $product_name = null;
    public ?string $product_description = null;
    public ?string $product_additional_item_property_name = null;
    public ?string $product_additional_item_property_value = null;
    public ?float $product_price = 0.00;
    public float $product_price_base_quantity = 1.00;
    public ?float $purchase_price = 0.00;
    public ?string $provider_name = null;
    
    // Get => string;  Set => int
    public ?string $family_id = '';
    
    // Get => string;  Set => int
    public ?string $tax_rate_id = '';
    
    // Get => string;  Set => int
    public ?string $unit_id = '';
    
    public ?string $unit_peppol_id = '';
    
    public ?int $product_tariff = null;
    
    public function __construct(array|object $product) {
        /**
         * @var string $product['product_sku']
         */
        $this->product_sku = $product['product_sku'] ?? '';
        /**
         * @var string $product['product_sii_schemeid']
         */
        $this->product_sii_schemeid = $product['product_sii_schemeid'] ?? '';
        /**
         * @var string $product['product_sii_id']
         */
        $this->product_sii_id = $product['product_sii_id'] ?? '';
        /**
         * @var string $product['product_icc_listid']
         */
        $this->product_icc_listid = $product['product_icc_listid'] ?? '';        
        /**
         * @var string $product['product_icc_listversionid']
         */
        $this->product_icc_listversionid = $product['product_icc_listversionid'] ?? '';
        /**
         * @var string $product['product_icc_id']
         */
        $this->product_icc_id = $product['product_icc_id'] ?? '';
        /**
         * @var string $product['product_country_of_origin_code']
         */
        $this->product_country_of_origin_code = $product['product_country_of_origin_code'] ?? '';
        /**
         * @var string $product['product_name']
         */
        $this->product_name = $product['product_name'] ?? '';
        /**
         * @var string $product['product_description']
         */
        $this->product_description = $product['product_description'] ?? '';
        /**
         * @var float|null $product['product_price']
         */
        $this->product_price = $product['product_price'] ?? 0.00;
        // how many items the selling price includes eg. 1 crate has 10 bags of cement
        /**
         * @var float $product['product_price_base_quantity']
         */
        $this->product_price_base_quantity = $product['product_price_base_quantity'] ?? 0.00;
        /**
         * @var float $product['purchase_price']
         */
        $this->purchase_price = $product['purchase_price'] ?? 0.00;
        /**
         * @var string $product['provider_name']
         */
        $this->provider_name = $product['provider_name'] ?? '';
        /**
         * @var int|null $product['product_tariff']
         */
        $this->product_tariff = $product['product_tariff'] ?? 0;
        /**
         * @var string $product['product_additional_item_property_name']
         */
        $this->product_additional_item_property_name = $product['product_additional_item_property_name'] ?? '';
        /**
         * @var string $product['product_additional_item_property_value']
         */
        $this->product_additional_item_property_value = $product['product_additional_item_property_value'] ?? '';
        /**
         * @var string|null $product['tax_rate_id']
         */
        $this->tax_rate_id = $product['tax_rate_id'] ?? '';
        /**
         * @var string|null $product['unit_id']
         */
        $this->unit_id = $product['unit_id'] ?? '';
        /**
         * @var string|null $product['unit_peppol_id']
         */
        $this->unit_peppol_id = $product['unit_peppol_id'] ?? '';
        /**
         * @var string|null $product['family_id']
         */
        $this->family_id = $product['family_id'] ?? '';
    }
               
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
