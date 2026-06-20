<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Product\ProductForm;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\FormModel\Field;
use Yiisoft\Translator\TranslatorInterface;

final readonly class ProductFormFields
{
    public function __construct(
        private TranslatorInterface $translator,
        private SettingRepository $settingRepository,
    ) {
    }

    /**
     * Family selection dropdown field for products
     * @param array<array-key, array<array-key, string>|string> $familiesData
     */
    public function familySelect(ProductForm $form, array $familiesData,
                                             bool $required = true): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control form-control-lg alert alert-warning' :
                'form-control form-control-lg alert alert-success';

        return Field::select($form, 'family_id')
            ->label($this->translator->translate('family'))
            ->addInputAttributes(['class' => $cssClass])
            ->value($form->family_id)
            ->prompt($this->translator->translate('none'))
            ->optionsData($familiesData)
            ->hint($this->translator->translate($hintKey))
            ->render();
    }

    /**
     * Unit selection dropdown field for products
     * @param array<array-key, array<array-key, string>|string> $unitsData
     */
    public function unitSelect(ProductForm $form, array $unitsData,
            bool $required = true): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control form-control-lg alert alert-warning' :
                'form-control form-control-lg alert alert-success';

        return Field::select($form, 'unit_id')
            ->label($this->translator->translate('unit'))
            ->addInputAttributes(['class' => $cssClass])
            ->value($form->unit_id)
            ->prompt($this->translator->translate('none'))
            ->optionsData($unitsData)
            ->hint($this->translator->translate($hintKey))
            ->render();
    }

    /**
     * Tax rate selection dropdown field for products
     * @param array<array-key, array<array-key, string>|string> $taxRatesData
     */
    public function taxRateSelect(ProductForm $form, array $taxRatesData,
            bool $required = true): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control form-control-lg alert alert-warning' :
                'form-control alert alert-success';

        return Field::select($form, 'tax_rate_id')
            ->label($this->translator->translate('tax.rate'))
            ->addInputAttributes(['class' => $cssClass])
            ->value($form->tax_rate_id)
            ->prompt($this->translator->translate('none'))
            ->optionsData($taxRatesData)
            ->hint($this->translator->translate($hintKey))
            ->render();
    }

    /**
     * Product text field with consistent styling
     */
    public function productTextField(
        ProductForm $form,
        string $fieldName,
        string $labelKey,
        bool $required = true,
        bool $isPrice = false,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control form-control-lg alert alert-warning' :
                'form-control form-control-lg alert alert-success';

        /** @var string|float|int|bool|null $value */
        $value = match ($fieldName) {
            'product_name' => $form->product_name,
            'product_description' => $form->product_description,
            'product_sku' => $form->product_sku,
            'purchase_price' => $form->purchase_price,
            'product_price' => $form->product_price,
            'product_price_base_quantity' => $form->product_price_base_quantity,
            'product_sii_id' => $form->product_sii_id,
            'product_sii_schemeid' => $form->product_sii_schemeid,
            'product_icc_listid' => $form->product_icc_listid,
            'product_icc_listversionid' => $form->product_icc_listversionid,
            'product_icc_id' => $form->product_icc_id,
            'product_country_of_origin_code' => $form->product_country_of_origin_code,
            'product_additional_item_property_name' => $form->product_additional_item_property_name,
            'product_additional_item_property_value' => $form->product_additional_item_property_value,
            'provider_name' => $form->provider_name,
            default => null,
        };

        if ($isPrice && is_numeric($value)) {
            $numericValue = is_float($value) ? $value : (float) $value;
            $value = $this->settingRepository->formatAmount(
                    $numericValue >= 0.00 ? $numericValue : 0.00);
        }

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes(['class' => $cssClass])
            ->value($value ?? '')
            ->placeholder($this->translator->translate($labelKey))
            ->hint($this->translator->translate($hintKey));

        if ($required) {
            $field = $field->required(true);
        }

        return $field->render();
    }

    /**
     * Product price field with proper formatting
     */
    public function productPriceField(ProductForm $form, string $fieldName,
            string $labelKey, bool $required = true): string
    {
        return $this->productTextField($form, $fieldName, $labelKey,
                $required, true);
    }

    /**
     * Unit Peppol selection dropdown field for products
     * @param array<array-key, array<array-key, string>|string> $unitPeppolsData
     */
    public function unitPeppolSelect(ProductForm $form, array $unitPeppolsData,
            bool $required = false): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control form-control-lg alert alert-warning' :
                'form-control form-control-lg alert alert-success';

        return Field::select($form, 'unit_peppol_id')
            ->label($this->translator->translate('product.peppol.unit'))
            ->addInputAttributes(['class' => $cssClass])
            ->value($form->unit_peppol_id)
            ->prompt($this->translator->translate('none'))
            ->optionsData($unitPeppolsData)
            ->hint($this->translator->translate($hintKey))
            ->render();
    }
}
