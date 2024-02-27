<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

$vat = $s->get_setting('enable_vat_registration') === '1' ? true : false;
?>
<?= Html::openTag('div', ['class' => 'panel panel-default']); ?>
    <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
        <?= I::tag()
            ->addAttributes([
                'tooltip' => 'data-bs-toggle', 
                'title' => $s->isDebugMode(2)
            ])
            ->content($translator->translate('invoice.product')); 
        ?>
    <?= Html::closeTag('div'); ?>    
    <?= Form::tag()
        ->post($urlGenerator->generate(...$action))
        ->enctypeMultipartFormData()
        ->csrf($csrf)
        ->id('QuoteItemForm')
        ->open() ?>
        
        <?= Html::openTag('div', ['class' => 'table-striped table-responsive']); ?>
            <?= Html::openTag('table', ['id' => 'item_table', 'class' => 'items table-primary table table-bordered no-margin']); ?>
                <?= Html::openTag('tbody', ['id' => 'new_inv_item_row']); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['rowspan' => '2', 'class' => 'td-icon']); ?>
                            <?= I::tag()
                                ->addClass('fa fa-arrows cursor-move'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-text']); ?>
                            <?= Field::hidden($form, 'quote_id')
                                ->hideLabel(); ?>
                            <?= Field::hidden($form, 'id')
                                ->hideLabel(); ?>
                            <?= Html::openTag('div' , ['class' => 'input-group', 'id' => 'product-no-task']); ?>    
                                <?php
                                    $optionsDataProduct = [];
                                    foreach ($products as $product) 
                                    {
                                        $optionsDataProduct[$product->getProduct_id()] = $product->getProduct_name();
                                    }
                                ?>
                                <?= Field::select($form, 'product_id')   
                                    ->optionsData($optionsDataProduct)    
                                    ->value(Html::encode($form->getProduct_id())); ?>
                            <?= Html::closeTag('div'); ?> 
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount td-quality']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::number($form, 'quantity')
                                    ->label($translator->translate('i.quantity'))
                                    ->addInputAttributes(['class' => 'input-lg form-control amount has-feedback'])
                                    ->value($numberHelper->format_amount($form->getQuantity()))
                                    ->hint($translator->translate('invoice.hint.greater.than.zero.please')); 
                               ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::text($form, 'price')
                                    ->label($translator->translate('i.price'))
                                    ->addInputAttributes(['class' => 'input-lg form-control amount has-feedback'])
                                    ->value($numberHelper->format_amount($form->getPrice() ?? 0.00))
                                    ->hint($translator->translate('invoice.hint.greater.than.zero.please')); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::text($form, 'discount_amount')
                                    ->label($translator->translate('i.item_discount'))
                                    ->addInputAttributes([
                                        'class' => 'input-lg form-control amount has-feedback',
                                        'data-bs-toggle' => 'tooltip',
                                        'data-placement' => 'bottom',
                                        'title' => $s->get_setting('currency_symbol') . ' ' . $translator->translate('i.per_item'),
                                    ])
                                    ->value($numberHelper->format_amount($form->getDiscount_amount() ?? 0.00)); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td td-vert-middle']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php
                                    $optionsDataTaxRate = [];
                                    foreach ($tax_rates as $tax_rate) 
                                    {
                                        $optionsDataTaxRate[$tax_rate->getTax_rate_id()] = $numberHelper->format_amount($tax_rate->getTax_rate_percent()) . '% - ' . $tax_rate->getTax_rate_name();
                                    }
                                ?>    
                                <?= Field::select($form, 'tax_rate_id')
                                    ->label($vat === false ? $translator->translate('i.tax_rate') : $translator->translate('invoice.invoice.vat.rate'))    
                                    ->addInputAttributes(['class' => 'form-control'])
                                    ->optionsData($optionsDataTaxRate)    
                                    ->value(Html::encode($form->getTax_rate_id()))
                                    ->hint($translator->translate('invoice.hint.this.field.is.required'));  
                                ?>        
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-icon text-right td-vert-middle']); ?>
                            <!-- see QuoteController: id modal-choose-items lies now on quote\view line 60
                                 which is used to open up the modal to select product items (quantity of 1) that go directly on the view
                                 The below button is used to save the details that are input on this form.
                            -->
                            <?= Html::openTag('button', [
                                'type' => 'submit',
                                'class' => 'btn btn-info', 
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'invitem/add_product']); ?>
                                <?= I::tag()->addClass('fa fa-plus'); ?>
                                <?= $translator->translate('i.save'); ?>
                            <?= Html::closeTag('button'); ?>
                        <?= Html::closeTag('td'); ?>              
                    <?= Html::closeTag('tr'); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php
                                    $optionsDataProductUnit = [];
                                    foreach ($units as $unit) 
                                    {
                                        $optionsDataProductUnit[$unit->getUnit_id()] = Html::encode($unit->getUnit_name()) . "/" . Html::encode($unit->getUnit_name_plrl());
                                    }
                                ?>    
                                <?= Field::select($form, 'product_unit_id')
                                    ->label($translator->translate('i.product_unit'))
                                    ->addInputAttributes(['class' => 'form-control'])
                                    ->optionsData($optionsDataProductUnit)    
                                    ->value(Html::encode($form->getProduct_unit_id() ?? ''))
                                    ->hint($translator->translate('invoice.hint.this.field.is.required'));
                                ?>            
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?= Html::openTag('span'); ?><?= $translator->translate('i.subtotal'); ?><?= Html::closeTag('span'); ?>
                                <?= Html::tag('br'); ?>    
                            <?= Html::openTag('span', ['name' => 'subtotal', 'class' => 'amount']); ?><?= Html::closeTag('span'); ?>        
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?= Html::openTag('span'); ?><?= $vat === false ? $translator->translate('i.discount') : $translator->translate('invoice.invoice.early.settlement.cash.discount') ?><?= Html::closeTag('span'); ?>
                                <?= Html::tag('br'); ?>    
                            <?= Html::openTag('span', ['name' => 'discount_total', 'class' => 'amount']); ?><?= Html::closeTag('span'); ?>        
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?= Html::openTag('span'); ?><?= $vat === false ? $translator->translate('i.tax') : $translator->translate('invoice.invoice.vat.abbreviation')  ?><?= Html::closeTag('span'); ?>
                                <?= Html::tag('br'); ?>    
                            <?= Html::openTag('span', ['name' => 'tax_total', 'class' => 'amount']); ?><?= Html::closeTag('span'); ?>        
                        <?= Html::closeTag('td'); ?>        
                        <?= Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?= Html::openTag('span'); ?><?= $translator->translate('i.total'); ?><?= Html::closeTag('span'); ?>
                                <?= Html::tag('br'); ?>    
                            <?= Html::openTag('span', ['name' => 'total', 'class' => 'amount']); ?><?= Html::closeTag('span'); ?>        
                        <?= Html::closeTag('td'); ?>
                    <?= Html::closeTag('tr'); ?>        
                <?= Html::closeTag('tbody'); ?>
            <?= Html::closeTag('table'); ?>
        <?= Html::closeTag('div'); ?>
        <?=Html::openTag('div', ['class' => 'col-xs-12 col-md-4']); ?>
            <?= Html::openTag('div', ['class' => 'btn-group']); ?>
                <?= Html::Tag('button', '', ['hidden' => 'hidden', 'class' => 'btn_quote_item_add_row btn btn-primary btn-md active bi bi-plus'])
                    ->content($translator->translate('i.add_new_row')); ?>           
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Form::tag()->close(); ?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div'); ?>