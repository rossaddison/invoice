<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;

/**
 * @var App\Infrastructure\Persistence\Product\Product $product
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Product\ProductForm $form
 * @var App\Invoice\ProductCustom\ProductCustomForm $productCustomForm
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var array $custom_fields
 * @var array $product_custom_values
 * @var array $custom_values
 * @psalm-var array<array-key, array<array-key, string>|string> $families
 * @psalm-var array<array-key, array<array-key, string>|string> $units
 * @psalm-var array<array-key, array<array-key, string>|string> $tax_rates
 * @psalm-var array<array-key, array<array-key, string>|string> $unit_peppols
 * @psalm-var array<array-key, array<array-key, string>|string> $product_types
 */

// A pure display page — was previously built from disabled Field:: widgets
// that rendered identically to the real editable form on product/edit,
// which confirmed live confused a user into trying to change Product Type
// here. ReadOnlyField (form-control-plaintext) makes it visually obvious
// nothing on this page is editable. See
// docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
$selectLabel = static function (array $optionsData, int|string|null $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $key = (string) $value;
    /** @var string|array|null $label */
    $label = $optionsData[$key] ?? null;
    return is_string($label) ? $label : $key;
};
?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('products.form'); ?>
<?= Html::closeTag('h1'); ?>

<?= Html::openTag('ul', ['id' => 'product-tabs', 'class' => 'nav nav-tabs nav-tabs-noborder']); ?>
    <?= Html::openTag('li', ['class' => 'active']); ?>
        <?=  new A()
            ->addAttributes([
                'data-bs-toggle' => 'tab',
                'class' => 'text-decoration-none',
            ])
            ->addClass('btn btn-danger me-1')
            ->content($translator->translate('product.form.tab.required'))
            ->href('#product-required')
            ->id('btn-reset')
            ->render();
?>
    <?= Html::closeTag('li'); ?>
    <?= Html::openTag('li'); ?>
        <?=  new A()
    ->addAttributes([
        'data-bs-toggle' => 'tab',
        'class' => 'text-decoration-none',
    ])
    ->addClass('btn btn-danger me-1')
    ->content($translator->translate('product.form.tab.not.required'))
    ->href('#product-not-required')
    ->id('btn-reset')
    ->render();
?>
    <?= Html::closeTag('li'); ?>
<?= Html::closeTag('ul'); ?>

<?= Html::openTag('div', ['class' => 'tabs-below']); ?>

    <?= Html::openTag('div', ['class' => 'tab-content']); ?>

        <?= Html::openTag('div', ['id' => 'product-required', 'class' => 'tab-pane active']); ?>
            <?php ReadOnlyField::render($translator->translate('product.name'), $form->product_name); ?>
            <?php ReadOnlyField::render($translator->translate('product.description'), $form->product_description); ?>
            <?php ReadOnlyField::render(
                $translator->translate('product.type'),
                $selectLabel($product_types, $form->product_type),
            ); ?>
            <?php ReadOnlyField::render(
                $translator->translate('family'),
                $selectLabel($families, $form->family_id),
            ); ?>
            <?php ReadOnlyField::render(
                $translator->translate('unit'),
                $selectLabel($units, $form->unit_id),
            ); ?>
            <?php ReadOnlyField::render(
                $translator->translate('tax.rate'),
                $selectLabel($tax_rates, $form->tax_rate_id),
            ); ?>
            <?php ReadOnlyField::render($translator->translate('product.sku'), $form->product_sku); ?>
            <?php ReadOnlyField::render(
                $translator->translate('purchase.price'),
                $s->formatAmount(($form->purchase_price ?? 0.00) >= 0.00 ? ($form->purchase_price ?? 0.00) : 0.00),
            ); ?>
            <?php ReadOnlyField::render(
                $translator->translate('product.price'),
                $s->formatAmount(($form->product_price ?? 0.00) >= 0.00 ? ($form->product_price ?? 0.00) : 0.00),
            ); ?>
            <?php ReadOnlyField::render(
                $translator->translate('product.price.base.quantity'),
                $s->formatAmount($form->product_price_base_quantity >= 0.00 ? $form->product_price_base_quantity : 0.00),
            ); ?>
        <?= Html::closeTag('div'); ?>

        <?= Html::openTag('div', ['id' => 'product-not-required', 'class' => 'tab-pane']); ?>

            <?php ReadOnlyField::render(
                $translator->translate('product.peppol.unit'),
                $selectLabel($unit_peppols, $form->unit_peppol_id),
            ); ?>
            <?php ReadOnlyField::render($translator->translate('product.sii.id'), $form->product_sii_id); ?>
            <?php ReadOnlyField::render($translator->translate('product.sii.schemeid'), $form->product_sii_schemeid); ?>
            <?php ReadOnlyField::render($translator->translate('product.icc.listid'), $form->product_icc_listid); ?>
            <?php ReadOnlyField::render(
                $translator->translate('product.icc.listversionid'),
                $form->product_icc_listversionid,
            ); ?>
            <?php ReadOnlyField::render($translator->translate('product.icc.id'), $form->product_icc_id); ?>
            <?php ReadOnlyField::render(
                $translator->translate('product.country.of.origin.code') . $s->where('default_country'),
                $form->product_country_of_origin_code,
            ); ?>
            <?php ReadOnlyField::render(
                $translator->translate('product.additional.item.property.name'),
                $form->product_additional_item_property_name,
            ); ?>
            <?php ReadOnlyField::render(
                $translator->translate('product.additional.item.property.value'),
                $form->product_additional_item_property_value,
            ); ?>
            <?php ReadOnlyField::render($translator->translate('provider.name'), $form->provider_name); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'card']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?= $translator->translate('product.custom.fields'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'card-body']); ?>
      <?php
        /**
         * @var App\Infrastructure\Persistence\CustomField\CustomField $customField
         */
        foreach ($custom_fields as $customField): ?>
          <?php $cvH->printFieldForView($customField, $productCustomForm, $product_custom_values); ?>
      <?php endforeach; ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= $button::back(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
