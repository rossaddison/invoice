<?php
declare(strict_types=1);

use Yiisoft\Form\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
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
<?= $s->trans('products_form'); ?>
<?= Html::closeTag('h1'); ?>
<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ProductForm')
    ->open()
?> 

<?= 
    $alert; 
?>
                    
<?= Field::errorSummary($form, $errors)
    ->header('this is a header')
    ->onlyAttributes(...['product_sku','tax_rate_id','product_price'])    
    ->showAllErrors()
    ->footer('this is a footer'); ?>                    
     
<?= Field::text($form, 'product_name')
    ->label($s->trans('product_name'))
    ->required(true)    
    ->addInputAttributes(['value' => Html::encode($body['product_name'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
                    
<?= Field::text($form, 'product_description')
    ->label($s->trans('product_description'))         
    ->addInputAttributes(['value' => Html::encode($body['product_description'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?>                    

<?= Field::select($form, 'family_id')
    ->label($s->trans('family'))         
    ->required(true)        
    ->addInputAttributes(['value' => Html::encode($body['family_id'] ?? '')])
    ->optionsData($families)
    ->hint($translator->translate('invoice.hint.this.field.is.required'));        
?>
                    
<?= Field::select($form, 'unit_id')
    ->label($s->trans('unit'))
    ->required(true)
    ->addInputAttributes(['value' => Html::encode($body['unit_id'] ?? '')])
    ->optionsData($units)
    ->hint($translator->translate('invoice.hint.this.field.is.required'));    
?>
                    
<?= Field::select($form, 'tax_rate_id')
    ->label($s->trans('product_unit'))    
    ->required(true)
    ->addInputAttributes(['value' => Html::encode($body['tax_rate_id'] ?? '')])
    ->optionsData($tax_rates)
    ->hint($translator->translate('invoice.hint.this.field.is.required'));    
?>
                    
<?= Field::text($form, 'product_sku')
    ->label($s->trans('product_sku'))    
    ->required(true)    
    ->addInputAttributes(['value' => Html::encode($body['product_sku'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>

<?= Html::a(); ?>                    
<?= Field::select($form, 'unit_peppol_id')
    ->label($translator->translate('invoice.product.peppol.unit'))        
    ->addInputAttributes(['value' => Html::encode($body['unit_peppol_id'] ?? '')])
    ->optionsData($unit_peppols)
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 

<?= Field::text($form, 'product_sii_id')
    ->label($translator->translate('invoice.product.sii.id'))        
    ->addInputAttributes(['value' => Html::encode($body['product_sii_id'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 
                    
<?= Field::text($form, 'product_sii_schemeid')
    ->label($translator->translate('invoice.product.sii.schemeid'))        
    ->addInputAttributes(['value' => Html::encode($body['product_sii_schemeid'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 

<?= Field::text($form, 'product_icc_listid')
    ->label($translator->translate('invoice.product.icc.listid'))        
    ->addInputAttributes(['value' => Html::encode($body['product_icc_listid'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 

<?= Field::text($form, 'product_icc_listversionid')
    ->label($translator->translate('invoice.product.icc.listversionid'))        
    ->addInputAttributes(['value' => Html::encode($body['product_icc_listversionid'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 

<?= Field::text($form, 'product_icc_id')
    ->label($translator->translate('invoice.product.icc.id'))        
    ->addInputAttributes(['value' => Html::encode($body['product_icc_id'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 

<?= Field::text($form, 'product_country_of_origin_code')
    ->label($translator->translate('invoice.product.country.of.origin.code').$s->where('default_country'))        
    ->addInputAttributes(['value' => Html::encode($body['product_country_of_origin_code'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 

<?= Field::text($form, 'product_additional_item_property_name')
    ->label($translator->translate('invoice.product.additional.item.property.name'))        
    ->addInputAttributes(['value' => Html::encode($body['product_additional_item_property_name'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?> 

<?= Field::text($form, 'product_additional_item_property_value')
    ->label($translator->translate('invoice.product.additional.item.property.value'))        
    ->addInputAttributes(['value' => Html::encode($body['product_additional_item_property_value'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?>         
    
<?= Field::number($form, 'product_price')
    ->label($s->trans('product_price'))        
    ->addInputAttributes(['value' => $s->format_amount((float)($body['product_price'] ?? 0.00))])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?>         

<?= Field::number($form, 'product_price_base_quantity')
    ->label($translator->translate('invoice.product.price.base.quantity'))        
    ->addInputAttributes(['value' => (float)($body['product_price_base_quantity'] ?? 1.00)])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?>         

<?= Field::text($form, 'provider_name')
    ->label($s->trans('provider_name'))        
    ->addInputAttributes(['value' => Html::encode($body['provider_name'] ?? '')])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?>         

<?= Field::number($form, 'purchase_price')
    ->label($s->trans('purchase_price'))        
    ->addInputAttributes(['value' => $s->format_amount((float)($body['purchase_price'] ?? 0.00))])
    ->min(0.00)
    ->max(1000.00)        
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?>         

<?= Field::number($form, 'product_tariff')
    ->label($s->trans('product_tariff'))        
    ->addInputAttributes(['value' => $s->format_amount((float)($body['product_tariff'] ?? 0.00))])
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); ?>         
                    
<?= Field::buttonGroup()
    ->addContainerClass('btn-group btn-toolbar float-end')
    ->buttonsData([
        [
            $translator->translate('invoice.cancel'),
            'type' => 'reset',
            'class' => 'btn btn-lg btn-danger',
            'name'=> 'btn_cancel'
        ],
        [
            $translator->translate('invoice.submit'),
            'type' => 'submit',
            'class' => 'btn btn-lg btn-primary',
            'name' => 'btn_send'
        ],
]) ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>