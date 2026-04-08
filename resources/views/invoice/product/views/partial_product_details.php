<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Entity\Product
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
 */
?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('products.form'); ?>
<?= Html::closeTag('h1'); ?>
<?=  new Form()
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ProductForm')
    ->open()
?>

<?= Html::openTag('ul', ['id' => 'product-tabs', 'class' => 'nav nav-tabs nav-tabs-noborder']); ?>
    <?= Html::openTag('li', ['class' => 'active']); ?>
        <?=  new A()
            ->addAttributes([
                'data-bs-toggle' => 'tab',
                'style' => 'text-decoration:none',
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
        'style' => 'text-decoration:none',
    ])
    ->addClass('btn btn-danger me-1')
    ->content($translator->translate('product.form.tab.not.required'))
    ->href('#product-not-required')
    ->id('btn-reset')
    ->render();
?>
    <?= Html::closeTag('li'); ?>
<?= Html::closeTag('ul'); ?>

<?= Html::openTag('div', ['class' => 'tabbable tabs-below']); ?>

    <?= Html::openTag('div', ['class' => 'tab-content']); ?>

        <?= Html::openTag('div', ['id' => 'product-required', 'class' => 'tab-pane active']); ?>
            <?= Field::text($form, 'product_name')
        ->disabled(true); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_description')
        ->disabled(true); ?>
            <?= Html::tag('br'); ?>
            <?= Field::select($form, 'family_id')
        ->label($translator->translate('family'))
        ->addInputAttributes([
            'class' => 'form-control  alert alert-warning',
        ])
        ->value($form->getFamilyId())
        ->prompt($translator->translate('none'))
        ->optionsData($families)
        ->disabled(true)
        ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::tag('br'); ?>
            <?= Field::select($form, 'unit_id')
    ->label($translator->translate('unit'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning',
    ])
    ->value($form->getUnitId())
    ->prompt($translator->translate('none'))
    ->optionsData($units)
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::tag('br'); ?>
            <?= Field::select($form, 'tax_rate_id')
    ->label($translator->translate('tax.rate'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning',
    ])
    ->optionsData($tax_rates)
    ->value($form->getTaxRateId())
    ->prompt($translator->translate('none'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_sku')
    ->label($translator->translate('product.sku'))
    ->required(true)
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning',
    ])
    ->value(Html::encode($form->getProductSku()))
    ->placeholder($translator->translate('product.sku'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'purchase_price')
    ->label($translator->translate('purchase.price'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning',
    ])
    ->value($s->formatAmount($form->getPurchasePrice() >= 0.00
                              ? $form->getPurchasePrice() : 0.00))
    ->placeholder($translator->translate('purchase.price'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_price')
    ->label($translator->translate('product.price'))
    ->required(true)
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning',
    ])
    ->disabled(true)
    ->value($s->formatAmount(($form->getProductPrice() >= 0.00
                               ? $form->getProductPrice() : 0.00)))
    ->placeholder($translator->translate('product.price'))

        ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::number($form, 'product_price_base_quantity')
    ->label($translator->translate('product.price.base.quantity'))
    ->required(true)
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning',
    ])
    ->disabled(true)
    ->value($s->formatAmount(($form->getProductPriceBaseQuantity() >= 0.00
                               ? $form->getProductPriceBaseQuantity() : 0.00)))
    ->placeholder($translator->translate('product.price.base.quantity'))
    ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
        <?= Html::closeTag('div'); ?>

        <?= Html::openTag('div', ['id' => 'product-not-required', 'class' => 'tab-pane']); ?>

            <?= Field::select($form, 'unit_peppol_id')
    ->label($translator->translate('product.peppol.unit'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->prompt($translator->translate('none'))
    ->optionsData($unit_peppols)
    ->value(Html::encode($form->getUnitPeppolId()))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_sii_id')
    ->label($translator->translate('product.sii.id'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->value(Html::encode($form->getProductSiiId()))
    ->placeholder($translator->translate('product.sii.id'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_sii_schemeid')
    ->label($translator->translate('product.sii.schemeid'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->value(Html::encode($form->getProductSiiSchemeid()))
    ->placeholder($translator->translate('product.sii.schemeid'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_icc_listid')
    ->label($translator->translate('product.icc.listid'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->value(Html::encode($form->getProductIccListid()))
    ->placeholder($translator->translate('product.icc.listid'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_icc_listversionid')
    ->label($translator->translate('product.icc.listversionid'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->value(Html::encode($form->getProductIccListversionid()))
    ->placeholder($translator->translate('product.icc.listversionid'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_icc_id')
    ->label($translator->translate('product.icc.id'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->value(Html::encode($form->getProductIccId()))
    ->placeholder($translator->translate('product.icc.id'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_country_of_origin_code')
    ->label($translator->translate('product.country.of.origin.code') . $s->where('default_country'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->value(Html::encode($form->getProductCountryOfOriginCode()))
    ->placeholder($translator->translate('product.country.of.origin.code') . $s->where('default_country'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_additional_item_property_name')
    ->label($translator->translate('product.additional.item.property.name'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->value(Html::encode($form->getProductAdditionalItemPropertyName()))
    ->placeholder($translator->translate('product.additional.item.property.name'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_additional_item_property_value')
    ->label($translator->translate('product.additional.item.property.value'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->value(Html::encode($form->getProductAdditionalItemPropertyValue()))
    ->placeholder($translator->translate('product.additional.item.property.value'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'provider_name')
    ->label($translator->translate('provider.name'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success',
    ])
    ->value(Html::encode($form->getProviderName()))
    ->placeholder($translator->translate('provider.name'))
    ->disabled(true)
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'panel panel-default']); ?>
    <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
        <?= $translator->translate('product.custom.fields'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'panel-body']); ?>
      <?php
        /**
         * @var App\Invoice\Entity\CustomField $customField
         */
        foreach ($custom_fields as $customField): ?>
          <?php $cvH->printFieldForView($customField, $productCustomForm, $product_custom_values); ?>
      <?php endforeach; ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= $button::back(); ?>
<?=  new Form()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>