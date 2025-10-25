<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Product\ProductForm $form
 * @var App\Invoice\ProductCustom\ProductCustomForm $productCustomForm
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var App\Widget\FormFields $formFields
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $customValues
 * @var array $customFields
 * @var array $productCustomValues
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $taxRates
 * @psalm-var array<array-key, array<array-key, string>|string> $families
 * @psalm-var array<array-key, array<array-key, string>|string> $units
 * @psalm-var array<array-key, array<array-key, string>|string> $unitPeppols
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
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ProductForm')
    ->open()
?> 

<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('product.error.summary'))
    ->onlyProperties(...['product_sku','tax_rate_id','product_price'])
    ->onlyCommonErrors()
?>

<?= Html::openTag('ul', ['id' => 'product-tabs', 'class' => 'nav nav-tabs']); ?>
    <?= Html::openTag('li', ['class' => 'nav-item']); ?>
        <?= A::tag()
            ->addAttributes([
                'data-bs-toggle' => 'tab',
                'data-bs-target' => '#product-required',
                'style' => 'text-decoration:none',
                'role' => 'tab',
                'aria-controls' => 'product-required',
                'aria-selected' => 'true',
            ])
            ->addClass('nav-link active')
            ->content($translator->translate('product.form.tab.required'))
            ->href('#product-required')
            ->id('required-tab')
            ->render();
?>
    <?= Html::closeTag('li'); ?>
    <?= Html::openTag('li', ['class' => 'nav-item']); ?>
        <?= A::tag()
            ->addAttributes([
                'data-bs-toggle' => 'tab',
                'data-bs-target' => '#product-not-required',
                'style' => 'text-decoration:none',
                'role' => 'tab',
                'aria-controls' => 'product-not-required',
                'aria-selected' => 'false',
            ])
            ->addClass('nav-link')
            ->content($translator->translate('product.form.tab.not.required'))
            ->href('#product-not-required')
            ->id('not-required-tab')
            ->render();
?>
    <?= Html::closeTag('li'); ?>    
<?= Html::closeTag('ul'); ?>

<?= Html::openTag('div', ['class' => 'tab-content', 'id' => 'product-tab-content']); ?>
    
    <?= Html::openTag('div', [
        'id' => 'product-required',
        'class' => 'tab-pane active',
        'role' => 'tabpanel',
        'aria-labelledby' => 'required-tab',
    ]); ?>
        <?= $formFields->productTextField($form, 'product_name', 'product.name', true); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_description', 'product.description', true); ?>                    
        <?= Html::tag('br'); ?>
        <?= $formFields->familySelect($form, $families, true); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->unitSelect($form, $units, true); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->taxRateSelect($form, $taxRates, true); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_sku', 'product.sku', true); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productPriceField($form, 'purchase_price', 'purchase.price', true); ?>         
        <?= Html::tag('br'); ?>
        <?= $formFields->productPriceField($form, 'product_price', 'product.price', true); ?>         
        <?= Html::tag('br'); ?>
        <?= $formFields->productPriceField($form, 'product_price_base_quantity', 'product.price.base.quantity', true); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productPriceField($form, 'product_tariff', 'product.tariff', true); ?>
        <?= Html::closeTag('div'); ?>

        <?= Html::openTag('div', [
            'id' => 'product-not-required',
            'class' => 'tab-pane',
            'role' => 'tabpanel',
            'aria-labelledby' => 'not-required-tab',
        ]); ?>
        
        <?= $formFields->unitPeppolSelect($form, $unitPeppols, false); ?> 
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_sii_id', 'product.sii.id', false); ?> 
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_sii_schemeid', 'product.sii.schemeid', false); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_icc_listid', 'product.icc.listid', false); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_icc_listversionid', 'product.icc.listversionid', false); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_icc_id', 'product.icc.id', false); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_country_of_origin_code', 'product.country.of.origin.code', false); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_additional_item_property_name', 'product.additional.item.property.name', false); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'product_additional_item_property_value', 'product.additional.item.property.value', false); ?>
        <?= Html::tag('br'); ?>
        <?= $formFields->productTextField($form, 'provider_name', 'provider.name', false); ?>
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
foreach ($customFields as $customField): ?>
          <?php $cvH->print_field_for_form($customField, $productCustomForm, $translator, $productCustomValues, $customValues); ?>
      <?php endforeach; ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= $button::backSave(); ?>
<?= Form::tag()->close(); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>