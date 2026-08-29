<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Product\ProductForm;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

final readonly class ProductFormFields
{
    // Extracted per this project's own SonarQube-duplication mandate —
    // both were already repeated 5-6 times across this file's various
    // select()/text() field builders before this constant existed.
    private const string REQUIRED_FIELD_CLASS = 'form-control form-control-lg alert alert-warning';
    private const string OPTIONAL_FIELD_CLASS = 'form-control form-control-lg alert alert-success';

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
        $cssClass = $required ? self::REQUIRED_FIELD_CLASS :
                self::OPTIONAL_FIELD_CLASS;

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
     * Product type (Product/Service) selection dropdown field for products
     * @param array<array-key, array<array-key, string>|string> $productTypesData
     */
    public function productTypeSelect(ProductForm $form, array $productTypesData,
            bool $required = true): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';
        $cssClass = $required ? self::REQUIRED_FIELD_CLASS :
                self::OPTIONAL_FIELD_CLASS;

        return Field::select($form, 'product_type')
            ->label($this->translator->translate('product.type'))
            ->addInputAttributes(['class' => $cssClass])
            ->value($form->product_type)
            ->prompt($this->translator->translate('none'))
            ->optionsData($productTypesData)
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
        $cssClass = $required ? self::REQUIRED_FIELD_CLASS :
                self::OPTIONAL_FIELD_CLASS;

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
        $cssClass = $required ? self::REQUIRED_FIELD_CLASS :
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
        $cssClass = $required ? self::REQUIRED_FIELD_CLASS :
                self::OPTIONAL_FIELD_CLASS;

        /** @var string|float|int|bool|null $value */
        $value = match ($fieldName) {
            'product_name' => $form->product_name,
            'product_description' => $form->product_description,
            'product_sku' => $form->product_sku,
            'purchase_price' => $form->purchase_price,
            'product_price' => $form->product_price,
            'retail_price' => $form->retail_price,
            'trade_min_order_qty' => $form->trade_min_order_qty,
            'trade_min_order_spend' => $form->trade_min_order_spend,
            'reorder_threshold' => $form->reorder_threshold,
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

        // Field::text()->value() strictly requires string|null (confirmed
        // live: InvalidArgumentException "Text field requires a string or
        // null value", trade_min_order_qty on a product whose DB row has
        // it NULL — so this was a POST-redisplay hydrating a blank ?int
        // field as 0, not null). '?? ""' only substitutes for a genuine
        // null; every ?int/?float property routed through this method
        // (trade_min_order_qty, trade_min_order_spend, reorder_threshold
        // when not $isPrice) can otherwise reach here as a real int/float,
        // which the field then rejects outright. Stringify anything left
        // over that isn't already null.
        $stringValue = $value === null ? null : (string) $value;

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes(['class' => $cssClass])
            ->value($stringValue ?? '')
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
     * The webshop-retail vs. B2B/wholesale option-button toggle — two
     * Bootstrap "checkbox/radio toggle buttons" (btn-check + label.btn),
     * not Field::radioList() (that binds a list of option values against
     * the form property directly, awkward for a bool-typed property like
     * available_on_webshop) and not a plain Field::checkbox() single
     * switch either — the point is naming both sides ("B2B / Wholesale"
     * vs. "Webshop / Retail"), not just an on/off toggle with one label.
     * Posts as ProductForm[available_on_webshop] either way (matching
     * $form->getFormName()), so App\Invoice\Product\ProductForm/
     * ProductService populate exactly the same as any other field here —
     * this is presentation only, not a different data path.
     */
    public function productAvailabilityField(ProductForm $form): string
    {
        $name = $form->getFormName() . '[available_on_webshop]';
        $webshopChecked = $form->available_on_webshop;
        $b2bId = 'product-availability-b2b';
        $webshopId = 'product-availability-webshop';

        return '<div class="mb-3">'
            . '<label class="form-label d-block">'
            . Html::encode($this->translator->translate('product.availability'))
            . '</label>'
            . '<div class="btn-group" role="group">'
            . Html::radio($name, '0', [
                'id' => $b2bId,
                'class' => 'btn-check',
                'autocomplete' => 'off',
            ])->checked(!$webshopChecked)->render()
            . '<label class="btn btn-outline-secondary" for="' . Html::encode($b2bId) . '">'
            . Html::encode($this->translator->translate('product.availability.b2b'))
            . '</label>'
            . Html::radio($name, '1', [
                'id' => $webshopId,
                'class' => 'btn-check',
                'autocomplete' => 'off',
            ])->checked($webshopChecked)->render()
            . '<label class="btn btn-outline-primary" for="' . Html::encode($webshopId) . '">'
            . Html::encode($this->translator->translate('product.availability.webshop'))
            . '</label>'
            . '</div>'
            . '<div class="form-text">'
            . Html::encode($this->translator->translate('product.availability.hint'))
            . '</div>'
            . '</div>';
    }

    /**
     * Stock-tracking toggle — a plain checkbox with the hidden '0'
     * fallback convention ProductService::saveProduct()'s isset()-only
     * check for track_stock expects (same shape as
     * partial_settings_front_page.php's own checkboxes and this class's
     * own productAvailabilityField() docblock note). Posts as
     * ProductForm[track_stock].
     */
    public function productStockTrackingField(ProductForm $form): string
    {
        $name = $form->getFormName() . '[track_stock]';
        $id = 'product-track-stock';

        return '<div class="mb-3 form-check">'
            . Html::hiddenInput($name, '0')->render()
            . Html::checkbox($name, '1', [
                'id' => $id,
                'class' => 'form-check-input',
            ])->checked($form->track_stock)->render()
            . '<label class="form-check-label" for="' . Html::encode($id) . '">'
            . Html::encode($this->translator->translate('product.track.stock'))
            . '</label>'
            . '</div>';
    }

    /**
     * Current stock — plain text, never a form input: Product::$stock_quantity
     * is a StockMovement-ledger cache, never editable directly (see that
     * property's own docblock). Null (a not-yet-saved product on the add()
     * screen) shows a placeholder instead of a number that would otherwise
     * misleadingly read as "0 in stock".
     */
    public function productStockQuantityDisplay(?float $stockQuantity): string
    {
        $value = $stockQuantity === null
            ? $this->translator->translate('product.stock.quantity.not.yet.available')
            : $this->settingRepository->formatAmount($stockQuantity);

        return '<div class="mb-3">'
            . '<label class="form-label d-block">'
            . Html::encode($this->translator->translate('product.stock.quantity'))
            . '</label>'
            . '<span class="form-control-plaintext">' . Html::encode($value) . '</span>'
            . '</div>';
    }

    /**
     * Unit Peppol selection dropdown field for products. Deliberately
     * not made $required=true — like Tax Rate Code
     * (taxrate.peppol.tax.rate.code.hint), the requirement is
     * conditional (only products actually sent on a Peppol invoice need
     * it), not universal. The generic hint.this.field.is.not.required
     * text used to sit here regardless, which was actively misleading
     * given PeppolHelperInvoiceLineTrait::validateInvItem() throws the
     * moment this product is used on a Peppol send — a dedicated hint
     * says so plainly instead.
     * @param array<array-key, array<array-key, string>|string> $unitPeppolsData
     */
    public function unitPeppolSelect(ProductForm $form, array $unitPeppolsData,
            bool $required = false): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' :
                'product.peppol.unit.hint';
        $cssClass = $required ? self::REQUIRED_FIELD_CLASS :
                self::OPTIONAL_FIELD_CLASS;

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
