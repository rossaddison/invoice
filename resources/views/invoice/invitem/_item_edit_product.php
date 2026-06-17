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
<?= Html::openTag('div', ['class' => 'card']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?=  new I()
            ->addClass('bi bi-info-circle')
            ->addAttributes([
                'tooltip' => 'data-bs-toggle',
                'title' => $s->isDebugMode(15),
            ])
            ->content(' ' . $translator->translate('product'));
?>
    <?= Html::closeTag('div'); ?>
    <?=  new Form()
->post($urlGenerator->generate($actionName, $actionArguments))
->enctypeMultipartFormData()
->csrf($csrf)
->id('InvItemForm')
->open() ?>

        <?= Html::openTag('div',
                ['class' => 'table-striped table-responsive']); ?>
            <?= Html::openTag('table',
                    ['id' => 'item_table',
                     'class' =>
                     'items table-primary table table-bordered m-0']); ?>
                <?= Html::openTag('tbody', ['id' => 'new_inv_item_row']); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td',
                                ['rowspan' => '2', 'class' => 'td-icon']); ?>
                            <?=  new I()
                                ->addClass('bi bi-grip-vertical cursor-move'); ?>
                                <?php if ($isRecurring) : ?>
                                    <?= Html::tag('br'); ?>
                                        <?=  new I()
                                        ->addAttributes([
                                            'title' => $translator->translate(
                                                    'recurring'),
                                            'class' =>
        'js-item-recurrence-toggler cursor-pointer bi bi-calendar text-muted',
                                        ]);
                                    ?>
                                    <?= Html::openTag('input',
                                            ['type' => 'hidden',
                                             'name' => 'is_recurring',
                                             'value' => '/']); ?>
                                <?php endif; ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td'); ?>
                            <?= Field::hidden($form, 'inv_id')
                                ->hideLabel(); ?>
                            <?= Field::hidden($form, 'task_id')
                                ->value('0')
                                ->hideLabel(); ?>
                            <?= Html::openTag('div',
                                ['id' => 'product-no-task']); ?>
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
                                ->value(Html::encode($form->product_id)); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td'); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::number($form, 'quantity')
                                    ->label($translator->translate('quantity'))
                                    ->value($numberHelper->formatAmount(
                                            $form->quantity))
                                    ->hint($translator->translate(
                                             'hint.greater.than.zero.please'));
                                ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::text($form, 'price')
                                    ->label($translator->translate('price'))
                                    ->value($numberHelper->formatAmount(
                                            $form->price ?? 0.00))
                                    ->hint($translator->translate(
                                           'hint.greater.than.zero.please')); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::text($form, 'discount_amount')
                                    ->label(
                                        $translator->translate('item.discount'))
                                    ->addInputAttributes([
                                        'data-bs-toggle' => 'tooltip',
                                        'data-placement' => 'bottom',
                                        'title' =>
                                            $s->getSetting('currency_symbol') .
                                            ' '
                                            . $translator->translate('per.item'),
                                    ])
                                    ->value($numberHelper->formatAmount(
                                       $form->discount_amount ?? 0.00)); ?>
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
                                    $taxRatePercent =
                                        $taxRate->getTaxRatePercent() ?? 0.00;
                                    $taxRateName = $taxRate->getTaxRateName()
                                            ?? '';
                                    $formattedNumber =
                                        $numberHelper->formatAmount(
                                            $taxRatePercent);
                                    if (($taxRatePercent >= 0.00)
                                            && (strlen($taxRateName) > 0)
                                                    && $formattedNumber
                                                               >= 0.00) {
                                        $optionsDataTaxRate[$taxRateId] =
                                                (string) $formattedNumber
                                            . '% - ' . $taxRateName;
                                    }
                                }
                                ?>
                                <?= Field::select($form, 'tax_rate_id')
                                    ->label($vat === false ?
                                            $translator->translate(
                                            'tax.rate') :
                                            $translator->translate('vat.rate'))
                                    ->addInputAttributes(
                                        ['class' => 'form-select',])
                                    ->optionsData($optionsDataTaxRate)
                                    ->value(Html::encode($form->tax_rate_id))
                                    ->hint($translator->translate(
                                                'hint.this.field.is.required'));
                                ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' =>
                                    'td-icon text-end td-vert-middle']); ?>
                            <!-- see line 896 InvController: id
                                modal-choose-items lies on
                                views/product/modal_product_lookups_inv.php-->
                            <?= Html::openTag('button', [
                                'type' => 'submit',
                                'class' => 'btn btn-info',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'invitem/addProduct']); ?>
                                <?=  new I()->addClass('bi bi-plus-lg'); ?>
                                <?= $translator->translate('save'); ?>
                            <?= Html::closeTag('button'); ?>
                        <?= Html::closeTag('td'); ?>
                    <?= Html::closeTag('tr'); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['class' => 'td-textarea']); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::textarea($form, 'description')
                                    ->value(Html::encode(
                                            $form->description ?? '')); ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::textarea($form, 'note')
                                    ->value(Html::encode($form->note ?? ''));
                                ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::text($form, 'order')
                                    ->value(Html::encode(
                                        $form->order ?? ''));
                                ?>
                            <?= Html::closeTag('div'); ?>
                            <?php if ($s->getSetting('enable_peppol') === '1') : ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::text($form, 'peppol_po_itemid')
                                    ->label($translator->translate('client.peppol.po.item.id'))
                                    ->value(Html::encode($form->peppol_po_itemid ?? ''));
                                ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::text($form, 'peppol_po_lineid')
                                    ->label($translator->translate('client.peppol.po.line.id'))
                                    ->value(Html::encode($form->peppol_po_lineid ?? ''));
                                ?>
                            <?= Html::closeTag('div'); ?>
                            <?php endif; ?>
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
                                        $unitNamePlrl =
                                                $unit->getUnitNamePlrl();
                                        if ((strlen($unitName) > 0)
                                                && (strlen($unitNamePlrl) > 0)) {
                                            $optionsDataProductUnit[$unitId]
                                                = Html::encode($unitName)
                                                . "/"
                                                . Html::encode($unitNamePlrl);
                                        }
                                    }
                                ?>
                                <?= Field::select($form, 'product_unit_id')
                                    ->label(
                                         $translator->translate('product.unit'))
                                    ->addInputAttributes(
                                            ['class' => 'form-select',])
                                    ->optionsData($optionsDataProductUnit)
                                    ->value(Html::encode(
                                        $form->product_unit_id ?? ''))
                                    ->hint($translator->translate(
                                        'hint.this.field.is.required'));
                                ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td',
                                ['class' => 'td-amount td-vert-middle']); ?>
                                <?= Html::tag('br'); ?>
                            <?= Html::openTag('span',
                                ['name' => 'subtotal', 'class' => 'text-end']); ?>
                            <?= Html::closeTag('span'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td',
                                ['class' => 'td-amount td-vert-middle']); ?>
                                <?= Html::tag('br'); ?>
                            <?= Html::openTag('span',
                                ['name' => 'discount_total',
                                    'class' => 'text-end']); ?>
                            <?= Html::closeTag('span'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td',
                                ['class' => 'td-amount td-vert-middle']); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td',
                                ['class' => 'td-amount td-vert-middle']); ?>
                        <?= Html::closeTag('td'); ?>
                    <?= Html::closeTag('tr'); ?>
                <?= Html::closeTag('tbody'); ?>
            <?= Html::closeTag('table'); ?>
        <?= Html::closeTag('div'); ?>
        <?=Html::openTag('div', ['class' => 'col-12 col-md-4']); ?>
            <?= Html::openTag('div', ['class' => 'btn-group']); ?>
                <?= Html::Tag('button', '',
                        ['hidden' => 'hidden',
                            'class' =>
            'btn_inv_item_add_row btn btn-primary btn-md active bi bi-plus'])
                    ->content($translator->translate('add.new.row')); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?=  new Form()->close(); ?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div'); ?>