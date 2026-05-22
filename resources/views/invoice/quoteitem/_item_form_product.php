<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\QuoteItem\QuoteItemForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $products
 * @var array $units
 * @var array $taxRates
 * @var string $alert
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $error
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataProduct
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTaxRate
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataProductUnit
 *
 */

$vat = $s->getSetting('enable_vat_registration') === '1';
?>
<?= Html::openTag('div', ['class' => 'card']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?=  new I()
            ->addClass('bi bi-info-circle')
            ->addAttributes([
                'tooltip' => 'data-bs-toggle',
                'title' => $s->isDebugMode(2),
            ])
            ->content(' ' . $translator->translate('product'));
?>
    <?= Html::closeTag('div'); ?>
    <?php
$action = $urlGenerator->generate($actionName, $actionArguments);
echo (new Form())
    ->post($action)
    ->csrf($csrf)
    ->id('QuoteItemFormAddProduct')
    ->addAttributes([
        'hx-post'              => $action,
        'hx-target'            => '#partial_item_table_parameters',
        'hx-swap'              => 'innerHTML',
        'hx-indicator'         => '#quote-item-saving',
        'hx-disabled-elt'      => '#btn-quote-item-save',
        'hx-on::after-request' => 'if(event.detail.successful) this.reset()',
    ])
    ->open();
?>

        <?= Html::openTag('div', ['class' => 'table-striped table-responsive']); ?>
            <?= Html::openTag('table', ['id' => 'item_table', 'class' => 'items table-primary table table-bordered no-margin']); ?>
                <?= Html::openTag('tbody', ['id' => 'new_quote_item_row']); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['rowspan' => '2', 'class' => 'td-icon']); ?>
                            <?=  new I()
                        ->addClass('bi bi-grip-vertical cursor-move'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-text']); ?>
                            <?= Field::hidden($form, 'quote_id')
                                ->hideLabel(); ?>
                            <?= Field::hidden($form, 'task_id')
                                ->value('0')
                                ->hideLabel(); ?>
                            <?= Html::openTag('div', ['id' => 'product-no-task']); ?>
                                <?php
                                    $optionsDataProduct = [];
/**
 * @var App\Infrastructure\Persistence\Product\Product $product
 */
foreach ($products as $product) {
    $productId = $product->reqId();
    $productName = $product->getProductName() ?? '';
    if (!empty($productId) && strlen($productName) > 0) {
        $optionsDataProduct[$productId] = $productName;
    }
}
?>
                                <?= Field::select($form, 'product_id')
    ->optionsData($optionsDataProduct)
    ->value(Html::encode($form->getProductId())); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount td-quality']); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::number($form, 'quantity')
    ->label($translator->translate('quantity'))
    ->addInputAttributes(['class' => 'form-control amount'])
    ->value($numberHelper->formatAmount($form->getQuantity()))
    ->hint($translator->translate('hint.greater.than.zero.please'));
?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::text($form, 'price')
     ->label($translator->translate('price'))
     ->addInputAttributes(['class' => 'form-control amount'])
     ->value($numberHelper->formatAmount($form->getPrice() ?? 0.00))
     ->hint($translator->translate('hint.greater.than.zero.please')); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::text($form, 'discount_amount')
     ->label($translator->translate('item.discount'))
     ->addInputAttributes([
         'class' => 'form-control amount',
         'data-bs-toggle' => 'tooltip',
         'data-placement' => 'bottom',
         'title' => $s->getSetting('currency_symbol') . ' ' . $translator->translate('per.item'),
     ])
     ->value($numberHelper->formatAmount($form->getDiscountAmount() ?? 0.00)); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td td-vert-middle']); ?>
                            <?= Html::openTag('div'); ?>
                                <?php
     $optionsDataTaxRate = [];
/**
 * @var App\Infrastructure\Persistence\TaxRate\TaxRate $taxRate
 */
foreach ($taxRates as $taxRate) {
    $taxRateId = $taxRate->reqId();
    $taxRatePercent = $taxRate->getTaxRatePercent() ?? 0.00;
    $taxRateName = $taxRate->getTaxRateName() ?? '';
    $formattedNumber = $numberHelper->formatAmount($taxRatePercent);
    if (($taxRatePercent >= 0.00) && (strlen($taxRateName) > 0) && $formattedNumber >= 0.00) {
        $optionsDataTaxRate[$taxRateId] = (string) $formattedNumber . '% - ' . $taxRateName;
    }
}
?>
                                <?= Field::select($form, 'tax_rate_id')
    ->label($vat === false ? $translator->translate('tax.rate') : $translator->translate('vat.rate'))
    ->addInputAttributes(['class' => 'form-select',])
    ->optionsData($optionsDataTaxRate)
    ->value(Html::encode($form->getTaxRateId()))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-icon text-right td-vert-middle']); ?>
                            <?= Html::openTag('button', [
                                'type'           => 'submit',
                                'id'             => 'btn-quote-item-save',
                                'class'          => 'btn btn-info',
                                'data-bs-toggle' => 'tooltip',
                                'title'          => $translator->translate('add.product')]); ?>
                                <?= new I()->addClass('bi bi-plus-lg'); ?>
                                <?= $translator->translate('save'); ?>
                            <?= Html::closeTag('button'); ?>
                            <?= Html::openTag('span', [
                                'id'    => 'quote-item-saving',
                                'class' => 'htmx-indicator ms-2',
                            ]); ?>
                                <?= new I()->addClass('bi bi-arrow-repeat spin'); ?>
                            <?= Html::closeTag('span'); ?>
                        <?= Html::closeTag('td'); ?>
                    <?= Html::closeTag('tr'); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['class' => 'td-textarea']); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::textarea($form, 'description')
    ->value(Html::encode($form->getDescription() ?? '')); ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::text($form, 'order')
    ->value(Html::encode($form->getOrder() ?? ''));
?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div'); ?>
                                <?php
    $optionsDataProductUnit = [];
/**
 * @var App\Infrastructure\Persistence\Unit\Unit $unit
 */
foreach ($units as $unit) {
    $unitId = $unit->reqId();
    $unitName = $unit->getUnitName();
    $unitNamePlrl = $unit->getUnitNamePlrl();
    if ((strlen($unitName) > 0) && (strlen($unitNamePlrl) > 0)) {
        $optionsDataProductUnit[$unitId] = Html::encode($unitName) . "/" . Html::encode($unitNamePlrl);
    }
}
?>
                                <?= Field::select($form, 'product_unit_id')
    ->label($translator->translate('product.unit'))
    ->addInputAttributes(['class' => 'form-select',])
    ->optionsData($optionsDataProductUnit)
    ->value(Html::encode($form->getProductUnitId() ?? ''))
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
        <?=Html::openTag('div', ['class' => 'col-12 col-md-4']); ?>
            <?= Html::openTag('div', ['class' => 'btn-group']); ?>
                <?= Html::Tag('button', '', ['hidden' => 'hidden', 'class' => 'btn_quote_item_add_row btn btn-primary btn-md active bi bi-plus'])
                    ->content($translator->translate('add.new.row')); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?=  new Form()->close(); ?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div'); ?>