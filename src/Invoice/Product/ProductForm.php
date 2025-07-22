<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Product;
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

    #[Required]
    public ?float  $product_price = 0.00;

    public float   $product_price_base_quantity = 1.00;

    #[Required]
    public ?float  $purchase_price = 0.00;

    public ?string $provider_name = null;

    #[Required]
    public ?string $family_id = '';

    #[Required]
    public ?string $tax_rate_id = '';

    #[Required]
    public ?string $unit_id = '';

    public ?string $unit_peppol_id = '';

    public ?float $product_tariff = 0.00;

    public function __construct(Product $product)
    {
        $this->product_sku = $product->getProduct_sku();
        $this->product_sii_schemeid = $product->getProduct_sii_schemeid();
        $this->product_sii_id = $product->getProduct_sii_id();
        $this->product_icc_listid = $product->getProduct_icc_listid();
        $this->product_icc_listversionid = $product->getProduct_icc_listversionid();
        $this->product_icc_id = $product->getProduct_icc_id();
        $this->product_country_of_origin_code = $product->getProduct_country_of_origin_code();
        $this->product_name = $product->getProduct_name();
        $this->product_description = $product->getProduct_description();
        $this->product_price = $product->getProduct_price();
        $this->product_price_base_quantity = $product->getProduct_price_base_quantity();
        $this->purchase_price = $product->getPurchase_price();
        $this->provider_name = $product->getProvider_name();
        $this->product_tariff = $product->getProduct_tariff();
        $this->product_additional_item_property_name = $product->getProduct_additional_item_property_name();
        $this->product_additional_item_property_value = $product->getProduct_additional_item_property_value();
        $this->tax_rate_id = $product->getTax_rate_id();
        $this->unit_id = $product->getUnit_id();
        $this->unit_peppol_id = $product->getUnit_peppol_id();
        $this->family_id = $product->getFamily_id();
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

    public function getProduct_tariff(): float|null
    {
        return $this->product_tariff;
    }

    /**
     * @return string
     *
     * @psalm-return 'ProductForm'
     */
    #[\Override]
    public function getFormName(): string
    {
        // used in ProductService to identify array
        return 'ProductForm';
    }
}
