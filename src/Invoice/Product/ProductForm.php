<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Infrastructure\Persistence\Product\Product;
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
    public ?int $family_id = null;

    #[Required]
    public ?int $tax_rate_id = null;

    #[Required]
    public ?int $unit_id = null;

    public ?int $unit_peppol_id = null;

    public static function show(Product $product): self
    {
        $form = new self();
        $form->product_sku = $product->getProductSku();
        $form->product_sii_schemeid = $product->getProductSiiSchemeid();
        $form->product_sii_id = $product->getProductSiiId();
        $form->product_icc_listid = $product->getProductIccListid();
        $form->product_icc_listversionid = $product->getProductIccListversionid();
        $form->product_icc_id = $product->getProductIccId();
        $form->product_country_of_origin_code =
            $product->getProductCountryOfOriginCode();
        $form->product_name = $product->getProductName();
        $form->product_description = $product->getProductDescription();
        $form->product_price = $product->getProductPrice();
        $form->product_price_base_quantity =
            $product->getProductPriceBaseQuantity();
        $form->purchase_price = $product->getPurchasePrice();
        $form->provider_name = $product->getProviderName();
        $form->product_additional_item_property_name =
            $product->getProductAdditionalItemPropertyName();
        $form->product_additional_item_property_value =
            $product->getProductAdditionalItemPropertyValue();
        $form->tax_rate_id = $product->reqTaxRateId();
        $form->unit_id = $product->reqUnitId();
        $form->unit_peppol_id = $product->getUnitPeppolId();
        $form->family_id = $product->reqId();
        return $form;
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
