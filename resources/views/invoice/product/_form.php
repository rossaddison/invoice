<?php
declare(strict_types=1);


use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var array $body
 * @var string $csrf
 * @var string $action
 */
?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= $translator->translate('i.products_form'); ?>
<?= Html::closeTag('h1'); ?>
<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ProductForm')
    ->open()
?> 

<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('invoice.product.error.summary'))
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
            ->content($translator->translate('invoice.product.form.tab.required'))
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
            ->content($translator->translate('invoice.product.form.tab.not.required'))
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
                ->label($translator->translate('i.product_name'))
                ->required(true)    
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])
                ->value(Html::encode($form->getProduct_name()))
                ->placeholder($translator->translate('i.product_name'))
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_description')
                ->label($translator->translate('i.product_description'))
                ->required(true)        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])
                ->value(Html::encode($form->getProduct_description()))    
                ->placeholder($translator->translate('i.product_description'))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>                    
            <?= Html::tag('br'); ?>
            <?= Field::select($form, 'family_id')
                ->label($translator->translate('i.family'))         
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])    
                ->value($form->getFamily_id())                
                ->prompt($translator->translate('i.none'))    
                ->optionsData($families)
                ->hint($translator->translate('invoice.hint.this.field.is.required'));        
            ?>
            <?= Html::tag('br'); ?>
            <?= Field::select($form, 'unit_id')
                ->label($translator->translate('i.unit'))
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])
                ->value($form->getUnit_id())                
                ->prompt($translator->translate('i.none'))    
                ->optionsData($units)
                ->hint($translator->translate('invoice.hint.this.field.is.required'));    
            ?>
            <?= Html::tag('br'); ?>
            <?= Field::select($form, 'tax_rate_id')
                ->label($translator->translate('i.tax_rate'))    
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])
                ->optionsData($tax_rates)
                ->value($form->getTax_rate_id())                
                ->prompt($translator->translate('i.none'))    
                ->hint($translator->translate('invoice.hint.this.field.is.required'));    
            ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_sku')
                ->label($translator->translate('i.product_sku'))    
                ->required(true)    
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])
                ->value(Html::encode($form->getProduct_sku()))    
                ->placeholder($translator->translate('i.product_sku'))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'purchase_price')
                ->label($translator->translate('i.purchase_price'))
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])
                ->value($s->format_amount((float)($form->getPurchase_price() ?? 0.00)))    
                ->placeholder($translator->translate('i.purchase_price'))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>         
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_price')
                ->label($translator->translate('i.product_price'))
                ->required(true)
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])
                ->value($s->format_amount((float)($form->getProduct_price() ?? 0.00)))    
                ->placeholder($translator->translate('i.product_price'))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>         
            <?= Html::tag('br'); ?>
            <?= Field::number($form, 'product_price_base_quantity')
                ->label($translator->translate('invoice.product.price.base.quantity'))
                ->required(true)        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])
                ->value($s->format_amount((float)($form->getProduct_price_base_quantity()  ?? 0.00)))        
                ->placeholder($translator->translate('i.product_price_base_quantity'))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_tariff')
                ->label($translator->translate('i.product_tariff'))
                ->required(true)
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning'
                ])
                ->value($s->format_amount((float)($form->getProduct_tariff() ?? 0.00)))    
                ->placeholder($translator->translate('i.product_tariff'))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
        <?= Html::closeTag('div'); ?>

        <?= Html::openTag('div', ['id' => 'product-not-required', 'class' => 'tab-pane']); ?>
            
            <?= Field::select($form, 'unit_peppol_id')
                ->label($translator->translate('invoice.product.peppol.unit'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->prompt($translator->translate('i.none'))        
                ->optionsData($unit_peppols)
                ->value(Html::encode($form->getUnit_peppol_id()))        
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_sii_id')
                ->label($translator->translate('invoice.product.sii.id'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->value(Html::encode($form->getProduct_sii_id()))     
                ->placeholder($translator->translate('invoice.product.sii.id'))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_sii_schemeid')
                ->label($translator->translate('invoice.product.sii.schemeid'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->value(Html::encode($form->getProduct_sii_schemeid()))    
                ->placeholder($translator->translate('invoice.product.sii.schemeid'))    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_icc_listid')
                ->label($translator->translate('invoice.product.icc.listid'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->value(Html::encode($form->getProduct_icc_listid()))    
                ->placeholder($translator->translate('invoice.product.icc.listid'))    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_icc_listversionid')
                ->label($translator->translate('invoice.product.icc.listversionid'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->value(Html::encode($form->getProduct_icc_listversionid()))    
                ->placeholder($translator->translate('invoice.product.icc.listversionid'))    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_icc_id')
                ->label($translator->translate('invoice.product.icc.id'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->value(Html::encode($form->getProduct_icc_id()))    
                ->placeholder($translator->translate('invoice.product.icc.id'))    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_country_of_origin_code')
                ->label($translator->translate('invoice.product.country.of.origin.code').$s->where('default_country'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->value(Html::encode($form->getProduct_country_of_origin_code()))    
                ->placeholder($translator->translate('invoice.product.country.of.origin.code').$s->where('default_country'))    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_additional_item_property_name')
                ->label($translator->translate('invoice.product.additional.item.property.name'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->value(Html::encode($form->getProduct_additional_item_property_name()))    
                ->placeholder($translator->translate('invoice.product.additional.item.property.name'))    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'product_additional_item_property_value')
                ->label($translator->translate('invoice.product.additional.item.property.value'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->value(Html::encode($form->getProduct_additional_item_property_value()))    
                ->placeholder($translator->translate('invoice.product.additional.item.property.value'))    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?>         
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'provider_name')
                ->label($translator->translate('i.provider_name'))        
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-success'
                ])
                ->value(Html::encode($form->getProvider_name()))    
                ->placeholder($translator->translate('i.provider_name'))    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?>             
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>     

<?= Html::openTag('div',['class'=>'panel panel-default']); ?>
    <?= Html::openTag('div',['class'=>'panel-heading']); ?>
        <?= $translator->translate('invoice.product.custom.fields'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div',['class'=>'panel-body']); ?>
      <?php foreach ($custom_fields as $custom_field): ?>
          <?= $cvH->print_field_for_form($custom_field, $productCustomForm, $translator, $product_custom_values, $custom_values); ?>
      <?php endforeach; ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= $button::back_save(); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>