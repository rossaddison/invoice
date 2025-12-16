<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\InvItem\InvItemForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $products
 * @var array $units
 * @var array $taxRates
 * @var bool $isRecurring
 * @var string $alert
 * @var string $csrf
 * @var string $actionName
 * @var string $addItemActionName
 * @var string $indexItemActionName
 * @var string $title
 * @var int $invItemAllowancesChargesCount
 * @var int $invItemAllowancesCharges
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string, Stringable|null|scalar> $addItemActionArguments
 * @psalm-var array<string, Stringable|null|scalar> $indexItemActionArguments
 * @psalm-var array<string,list<string>> $error
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataProduct
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTaxRate
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataProductUnit
 *
 */

$vat = $s->getSetting('enable_vat_registration') === '1' ? true : false;
?>
<?= Html::openTag('div', ['class' => 'panel panel-default']); ?>
    <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
        <?= I::tag()
            ->addClass('bi bi-info-circle')
            ->addAttributes([
                'tooltip' => 'data-bs-toggle',
                'title' => $s->isDebugMode(15),
            ])
            ->content(' ' . $translator->translate('product'));
?>
    <?= Html::closeTag('div'); ?>    
    <?= Form::tag()
->post($urlGenerator->generate($actionName, $actionArguments))
->enctypeMultipartFormData()
->csrf($csrf)
->id('InvItemForm')
->open() ?>
        
        <?= Html::openTag('div', ['class' => 'table-striped table-responsive']); ?>
            <?= Html::openTag('table', ['id' => 'item_table', 'class' => 'items table-primary table table-bordered no-margin']); ?>
                <?= Html::openTag('tbody', ['id' => 'new_inv_item_row']); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['rowspan' => '2', 'class' => 'td-icon']); ?>
                            <?= I::tag()
                        ->addClass('fa fa-arrows cursor-move'); ?> 
                                <?php if ($isRecurring) : ?>
                                    <?= Html::tag('br'); ?>
                                        <?= I::tag()
                                    ->addAttributes([
                                        'title' => $translator->translate('recurring'),
                                        'class' => 'js-item-recurrence-toggler cursor-pointer fa fa-calendar-o text-muted',
                                    ]);
                                    ?>
                                        <?= Html::openTag('input', ['type' => 'hidden', 'name' => 'is_recurring', 'value' => '/']); ?>
                                <?php endif; ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-text']); ?>
                            <?= Field::hidden($form, 'inv_id')
                                ->hideLabel(); ?>
                            <?= Field::hidden($form, 'id')
                                ->hideLabel(); ?>
                            <?= Field::hidden($form, 'task_id')
                                ->value('0')
                                ->hideLabel(); ?>
                            <?= Html::openTag('div', ['class' => 'input-group', 'id' => 'product-no-task']); ?>    
                                <?php
                                    $optionsDataProduct = [];
/**
 * @var App\Invoice\Entity\Product $product
 */
foreach ($products as $product) {
    $productId = $product->getProduct_id();
    $productName = $product->getProduct_name() ?? '';
    if (!empty($productId) && strlen($productName) > 0) {
        $optionsDataProduct[$productId] = $productName;
    }
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
    ->label($translator->translate('quantity'))
    ->addInputAttributes(['class' => 'input-lg form-control amount has-feedback'])
    ->value($numberHelper->format_amount($form->getQuantity()))
    ->hint($translator->translate('hint.greater.than.zero.please'));
?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::text($form, 'price')
     ->label($translator->translate('price'))
     ->addInputAttributes(['class' => 'input-lg form-control amount has-feedback'])
     ->value($numberHelper->format_amount($form->getPrice() ?? 0.00))
     ->hint($translator->translate('hint.greater.than.zero.please')); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::text($form, 'discount_amount')
     ->label($translator->translate('item.discount'))
     ->addInputAttributes([
         'class' => 'input-lg form-control amount has-feedback',
         'data-bs-toggle' => 'tooltip',
         'data-placement' => 'bottom',
         'title' => $s->getSetting('currency_symbol') . ' ' . $translator->translate('per.item'),
     ])
     ->value($numberHelper->format_amount($form->getDiscount_amount() ?? 0.00)); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?php
                            // if no allowance or charge is associated with this item show the add button
                            if ($invItemAllowancesChargesCount == 0) {
                                $add = $translator->translate('allowance.or.charge.item.add');
                                $url = $urlGenerator->generate($addItemActionName, $addItemActionArguments);
                                ?>
                            <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                                <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Html::i(
                                    Html::a(
                                        '  ' . $add,
                                        $url,
                                        ['class' => 'btn btn-primary',
                                            'style' => 'font-family:Arial'],
                                    ),
                                    ['class' => 'btn btn-primary fa fa-plus'],
                                ); ?>
                                 <?= Html::closeTag('div'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?php // == 0
                                } ?>
                             <?php
                                 // if one or more allowance/charge is associated with this item show the index button
                                 if ($invItemAllowancesChargesCount > 0) {
                                     $add = $translator->translate('allowance.or.charge');
                                     $url = $urlGenerator->generate($indexItemActionName, $indexItemActionArguments);
                                     ?>
                            <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                                <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                    <?= Html::i(
                                        Html::a(
                                            '  ' . $add,
                                            $url,
                                            ['class' => 'btn btn-primary',
                                              'style' => 'font-family:Arial'],
                                        ),
                                        ['class' => 'btn btn-primary fa fa-item'],
                                    ); ?>
                                 <?= Html::closeTag('div'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?php // == 0
                        } ?>

                        <?= Html::openTag('td', ['class' => 'td td-vert-middle']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php
                                    $optionsDataTaxRate = [];
/**
 * @var App\Invoice\Entity\TaxRate $taxRate
 */
foreach ($taxRates as $taxRate) {
    $taxRateId = $taxRate->getTaxRateId();
    $taxRatePercent = $taxRate->getTaxRatePercent() ?? 0.00;
    $taxRateName = $taxRate->getTaxRateName() ?? '';
    $formattedNumber = $numberHelper->format_amount($taxRatePercent);
    if ((null !== $taxRateId) && ($taxRatePercent >= 0.00) && (strlen($taxRateName) > 0) && $formattedNumber >= 0.00) {
        $optionsDataTaxRate[$taxRateId] = (string) $formattedNumber . '% - ' . $taxRateName;
    }
}
?>    
                                <?= Field::select($form, 'tax_rate_id')
    ->label($vat === false ? $translator->translate('tax.rate') : $translator->translate('vat.rate'))
    ->addInputAttributes(['class' => 'form-control'])
    ->optionsData($optionsDataTaxRate)
    ->value(Html::encode($form->getTax_rate_id()))
    ->hint($translator->translate('hint.this.field.is.required'));
?>        
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-icon text-right td-vert-middle']); ?>
                            <!-- see line 896 InvController: id modal-choose-items lies on views/product/modal_product_lookups_inv.php-->
                            <?= Html::openTag('button', [
                                'type' => 'submit',
                                'class' => 'btn btn-info',
                                'data-toggle' => 'tooltip',
                                'title' => 'invitem/add_product']); ?>
                                <?= I::tag()->addClass('fa fa-plus'); ?>
                                <?= $translator->translate('save'); ?>
                            <?= Html::closeTag('button'); ?>
                        <?= Html::closeTag('td'); ?>              
                    <?= Html::closeTag('tr'); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['class' => 'td-textarea']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::textarea($form, 'description')
    ->value(Html::encode($form->getDescription() ?? '')); ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::textarea($form, 'note')
    ->value(Html::encode($form->getNote() ?? ''));
?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::text($form, 'order')
    ->value(Html::encode($form->getOrder() ?? ''));
?>
                            <?= Html::closeTag('div'); ?>    
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php
    $optionsDataProductUnit = [];
/**
 * @var App\Invoice\Entity\Unit $unit
 */
foreach ($units as $unit) {
    $unitId = $unit->getUnit_id();
    $unitName = $unit->getUnit_name();
    $unitNamePlrl = $unit->getUnit_name_plrl();
    if ((null !== $unitId) && (strlen($unitName) > 0) && (strlen($unitNamePlrl) > 0)) {
        $optionsDataProductUnit[$unitId] = Html::encode($unitName) . "/" . Html::encode($unitNamePlrl);
    }
}
?>    
                                <?= Field::select($form, 'product_unit_id')
    ->label($translator->translate('product.unit'))
    ->addInputAttributes(['class' => 'form-control'])
    ->optionsData($optionsDataProductUnit)
    ->value(Html::encode($form->getProduct_unit_id() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>            
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?= Html::openTag('span'); ?><?= $translator->translate('subtotal'); ?><?= Html::closeTag('span'); ?>
                                <?= Html::tag('br'); ?>    
                            <?= Html::openTag('span', ['name' => 'subtotal', 'class' => 'amount']); ?><?= Html::closeTag('span'); ?>        
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?= Html::openTag('span'); ?><?= $vat === false ? $translator->translate('discount') : $translator->translate('early.settlement.cash.discount') ?><?= Html::closeTag('span'); ?>
                                <?= Html::tag('br'); ?>    
                            <?= Html::openTag('span', ['name' => 'discount_total', 'class' => 'amount']); ?><?= Html::closeTag('span'); ?>        
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?= Html::openTag('span'); ?><?= $translator->translate('item.charge') ?><?= Html::closeTag('span'); ?>
                                <?= Html::tag('br'); ?>    
                            <?= Html::openTag('span', ['name' => 'charge_total', 'class' => 'amount']); ?><?= Html::closeTag('span'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?= Html::openTag('span'); ?><?= $vat === false ? $translator->translate('tax') : $translator->translate('vat.abbreviation')  ?><?= Html::closeTag('span'); ?>
                                <?= Html::tag('br'); ?>    
                            <?= Html::openTag('span', ['name' => 'tax_total', 'class' => 'amount']); ?><?= Html::closeTag('span'); ?>        
                        <?= Html::closeTag('td'); ?>        
                        <?= Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?= Html::openTag('span'); ?><?= $translator->translate('total'); ?><?= Html::closeTag('span'); ?>
                                <?= Html::tag('br'); ?>    
                            <?= Html::openTag('span', ['name' => 'total', 'class' => 'amount']); ?><?= Html::closeTag('span'); ?>        
                        <?= Html::closeTag('td'); ?>
                    <?= Html::closeTag('tr'); ?>        
                <?= Html::closeTag('tbody'); ?>
            <?= Html::closeTag('table'); ?>
        <?= Html::closeTag('div'); ?>
        <?=Html::openTag('div', ['class' => 'col-xs-12 col-md-4']); ?>
            <?= Html::openTag('div', ['class' => 'btn-group']); ?>
                <?= Html::Tag('button', '', ['hidden' => 'hidden', 'class' => 'btn_inv_item_add_row btn btn-primary btn-md active bi bi-plus'])
                    ->content($translator->translate('add.new.row')); ?>           
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Form::tag()->close(); ?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div'); ?>