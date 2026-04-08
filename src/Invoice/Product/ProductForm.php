<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Product;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;

final class ProductForm extends FormModel
{
    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $product_sku = null;

    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $product_sii_schemeid = null;

    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $product_sii_id = null;

    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $product_icc_listid = null;

    #[Length(min: 0, max: 20, skipOnEmpty: true)]
    public ?string $product_icc_listversionid = null;

    #[Length(min: 0, max: 50, skipOnEmpty: true)]
    public ?string $product_icc_id = null;

    #[Length(min: 0, max: 2, skipOnEmpty: true)]
    public ?string $product_country_of_origin_code = null;

    #[Length(min: 0, max: 200, skipOnEmpty: true)]
    public ?string $product_name = null;

    public ?string $product_description = null;

    #[Length(min: 0, max: 100, skipOnEmpty: true)]
    public ?string $product_additional_item_property_name = null;

    #[Length(min: 0, max: 200, skipOnEmpty: true)]
    public ?string $product_additional_item_property_value = null;

    #[Required]
    #[Number(min: 0, max: 999999999999999999)]
    public ?float  $product_price = 0.00;

    #[Required]
    #[Number(min: 0.01, max: 999999999999999999)]
    public float   $product_price_base_quantity = 1.00;

    #[Required]
    #[Number(min: 0, max: 999999999999999999)]
    public ?float  $purchase_price = 0.00;

    #[Length(min: 0, max: 255, skipOnEmpty: true)]
    public ?string $provider_name = null;

    #[Required]
    public ?string $family_id = '';

    #[Required]
    public ?string $tax_rate_id = '';

    #[Required]
    public ?string $unit_id = '';

    public ?string $unit_peppol_id = '';

    public function __construct(Product $product)
    {
        $this->product_sku = $product->getProductSku();
        $this->product_sii_schemeid = $product->getProductSiiSchemeid();
        $this->product_sii_id = $product->getProductSiiId();
        $this->product_icc_listid = $product->getProductIccListid();
        $this->product_icc_listversionid = $product->getProductIccListversionid();
        $this->product_icc_id = $product->getProductIccId();
        $this->product_country_of_origin_code = $product->getProductCountryOfOriginCode();
        $this->product_name = $product->getProductName();
        $this->product_description = $product->getProductDescription();
        $this->product_price = $product->getProductPrice();
        $this->product_price_base_quantity = $product->getProductPriceBaseQuantity();
        $this->purchase_price = $product->getPurchasePrice();
        $this->provider_name = $product->getProviderName();
        $this->product_additional_item_property_name = $product->getProductAdditionalItemPropertyName();
        $this->product_additional_item_property_value = $product->getProductAdditionalItemPropertyValue();
        $this->tax_rate_id = $product->getTaxRateId();
        $this->unit_id = $product->getUnitId();
        $this->unit_peppol_id = $product->getUnitPeppolId();
        $this->family_id = $product->getFamilyId();
    }

    public function getProductSku(): ?string
    {
        return $this->product_sku;
    }

    public function getProductSiiSchemeid(): ?string
    {
        return $this->product_sii_schemeid;
    }

    public function getProductSiiId(): ?string
    {
        return $this->product_sii_id;
    }

    public function getProductIccListid(): ?string
    {
        return $this->product_icc_listid;
    }

    public function getProductIccListversionid(): ?string
    {
        return $this->product_icc_listversionid;
    }

    public function getProductIccId(): ?string
    {
        return $this->product_icc_id;
    }

    public function getProductCountryOfOriginCode(): ?string
    {
        return $this->product_country_of_origin_code;
    }

    public function getProductName(): ?string
    {
        return $this->product_name;
    }

    public function getProductDescription(): ?string
    {
        return $this->product_description;
    }

    public function getProductPrice(): ?float
    {
        return $this->product_price;
    }

    public function getProductPriceBaseQuantity(): float
    {
        return $this->product_price_base_quantity;
    }

    public function getPurchasePrice(): ?float
    {
        return $this->purchase_price;
    }

    public function getProviderName(): ?string
    {
        return $this->provider_name;
    }

    public function getProductAdditionalItemPropertyName(): ?string
    {
        return $this->product_additional_item_property_name;
    }

    public function getProductAdditionalItemPropertyValue(): ?string
    {
        return $this->product_additional_item_property_value;
    }

    public function getFamilyId(): ?string
    {
        return $this->family_id;
    }

    public function getTaxRateId(): ?string
    {
        return $this->tax_rate_id;
    }

    public function getUnitId(): ?string
    {
        return $this->unit_id;
    }

    public function getUnitPeppolId(): ?string
    {
        return $this->unit_peppol_id;
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
