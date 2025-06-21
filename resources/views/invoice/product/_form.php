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

<?= Html::openTag('ul', ['id' => 'product-tabs', 'class' => 'nav nav-tabs nav-tabs-noborder']); ?>
    <?= Html::openTag('li', ['class' => 'active']); ?>
        <?= A::tag()
            ->addAttributes([
                'data-bs-toggle' => 'tab',
                'style' => 'text-decoration:none'
            ])
            ->addClass('btn btn-danger me-1')
            ->content($translator->translate('product.form.tab.required'))
            ->href('#product-required')
            ->id('btn-reset')
            ->render();
?>
    <?= Html::closeTag('li'); ?>
    <?= Html::openTag('li'); ?>
        <?= A::tag()
    ->addAttributes([
        'data-bs-toggle' => 'tab',
        'style' => 'text-decoration:none'
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
        ->label($translator->translate('product.name'))
        ->required(true)
        ->addInputAttributes([
            'class' => 'form-control  alert alert-warning'
        ])
        ->value(Html::encode($form->getProduct_name()))
        ->placeholder($translator->translate('product.name'))
        ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_description')
        ->label($translator->translate('product.description'))
        ->required(true)
        ->addInputAttributes([
            'class' => 'form-control  alert alert-warning'
        ])
        ->value(Html::encode($form->getProduct_description()))
        ->placeholder($translator->translate('product.description'))
        ->hint($translator->translate('hint.this.field.is.required')); ?>                    
            <?= Html::tag('br'); ?>
            <?= Field::select($form, 'family_id')
        ->label($translator->translate('family'))
        ->addInputAttributes([
            'class' => 'form-control  alert alert-warning'
        ])
        ->value($form->getFamily_id())
        ->prompt($translator->translate('none'))
        ->optionsData($families)
        ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::tag('br'); ?>
            <?= Field::select($form, 'unit_id')
    ->label($translator->translate('unit'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning'
    ])
    ->value($form->getUnit_id())
    ->prompt($translator->translate('none'))
    ->optionsData($units)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::tag('br'); ?>
            <?= Field::select($form, 'tax_rate_id')
    ->label($translator->translate('tax.rate'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning'
    ])
    ->optionsData($taxRates)
    ->value($form->getTax_rate_id())
    ->prompt($translator->translate('none'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_sku')
    ->label($translator->translate('product.sku'))
    ->required(true)
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning'
    ])
    ->value(Html::encode($form->getProduct_sku()))
    ->placeholder($translator->translate('product.sku'))
    ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'purchase_price')
    ->label($translator->translate('purchase.price'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning'
    ])
    ->value($s->format_amount(($form->getPurchase_price() >= 0.00 ?
                               $form->getPurchase_price() : 0.00)))
    ->placeholder($translator->translate('purchase.price'))
    ->hint($translator->translate('hint.this.field.is.required')); ?>         
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_price')
    ->label($translator->translate('product.price'))
    ->required(true)
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning'
    ])
    ->value($s->format_amount($form->getProduct_price() >= 0.00 ?
                              $form->getProduct_price() : 0.00))
    ->placeholder($translator->translate('product.price'))
    ->hint($translator->translate('hint.this.field.is.required')); ?>         
            <?= Html::tag('br'); ?>
            <?= Field::number($form, 'product_price_base_quantity')
    ->label($translator->translate('product.price.base.quantity'))
    ->required(true)
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning'
    ])
    ->value($s->format_amount($form->getProduct_price_base_quantity() >= 0.00 ?
                              $form->getProduct_price_base_quantity() : 0.00))
    ->placeholder($translator->translate('product.price.base.quantity'))
    ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_tariff')
    ->label($translator->translate('product.tariff'))
    ->required(true)
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning'
    ])
    ->value($s->format_amount(($form->getProduct_tariff() >= 0.00 ?
                               $form->getProduct_tariff() : 0.00)))
    ->placeholder($translator->translate('product.tariff'))
    ->hint($translator->translate('hint.this.field.is.required')); ?>
        <?= Html::closeTag('div'); ?>

        <?= Html::openTag('div', ['id' => 'product-not-required', 'class' => 'tab-pane']); ?>
            
            <?= Field::select($form, 'unit_peppol_id')
    ->label($translator->translate('product.peppol.unit'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->prompt($translator->translate('none'))
    ->optionsData($unitPeppols)
    ->value(Html::encode($form->getUnit_peppol_id()))
    ->hint($translator->translate('hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_sii_id')
    ->label($translator->translate('product.sii.id'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->value(Html::encode($form->getProduct_sii_id()))
    ->placeholder($translator->translate('product.sii.id'))
    ->hint($translator->translate('hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_sii_schemeid')
    ->label($translator->translate('product.sii.schemeid'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->value(Html::encode($form->getProduct_sii_schemeid()))
    ->placeholder($translator->translate('product.sii.schemeid'))
    ->hint($translator->translate('hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_icc_listid')
    ->label($translator->translate('product.icc.listid'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->value(Html::encode($form->getProduct_icc_listid()))
    ->placeholder($translator->translate('product.icc.listid'))
    ->hint($translator->translate('hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_icc_listversionid')
    ->label($translator->translate('product.icc.listversionid'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->value(Html::encode($form->getProduct_icc_listversionid()))
    ->placeholder($translator->translate('product.icc.listversionid'))
    ->hint($translator->translate('hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_icc_id')
    ->label($translator->translate('product.icc.id'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->value(Html::encode($form->getProduct_icc_id()))
    ->placeholder($translator->translate('product.icc.id'))
    ->hint($translator->translate('hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_country_of_origin_code')
    ->label($translator->translate('product.country.of.origin.code').$s->where('default_country'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->value(Html::encode($form->getProduct_country_of_origin_code()))
    ->placeholder($translator->translate('product.country.of.origin.code').$s->where('default_country'))
    ->hint($translator->translate('hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_additional_item_property_name')
    ->label($translator->translate('product.additional.item.property.name'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->value(Html::encode($form->getProduct_additional_item_property_name()))
    ->placeholder($translator->translate('product.additional.item.property.name'))
    ->hint($translator->translate('hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_additional_item_property_value')
    ->label($translator->translate('product.additional.item.property.value'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->value(Html::encode($form->getProduct_additional_item_property_value()))
    ->placeholder($translator->translate('product.additional.item.property.value'))
    ->hint($translator->translate('hint.this.field.is.not.required')); ?>         
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'provider_name')
    ->label($translator->translate('provider.name'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-success'
    ])
    ->value(Html::encode($form->getProvider_name()))
    ->placeholder($translator->translate('provider.name'))
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