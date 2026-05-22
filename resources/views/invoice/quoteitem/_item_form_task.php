<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\QuoteItem\QuoteItemForm $form
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $tasks
 * @var array $taxRates
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string, null|string> $optionsDataTask
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTaxRate
 */

$vat = $s->getSetting('enable_vat_registration') === '1';
?>
<?= Html::openTag('div', ['class' => 'card']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?=  new I()
            ->addClass('bi bi-info-circle')
            ->addAttributes([
                'tooltip' => 'data-bs-toggle',
                'title' => $s->isDebugMode(13),
            ]);
?>
    <?= Html::closeTag('div'); ?>
    <?php
$action = $urlGenerator->generate($actionName, $actionArguments);
echo (new Form())
    ->post($action)
    ->csrf($csrf)
    ->id('QuoteItemFormAddTask')
    ->addAttributes([
        'hx-post'              => $action,
        'hx-target'            => '#partial_item_table_parameters',
        'hx-swap'              => 'innerHTML',
        'hx-indicator'         => '#quote-task-saving',
        'hx-disabled-elt'      => '#btn-quote-task-save',
        'hx-on::after-request' => 'if(event.detail.successful) this.reset()',
    ])
    ->open();
?>

        <?= Html::openTag('div', ['class' => 'table-striped table-responsive']); ?>
            <?= Html::openTag('table', ['id' => 'item_table', 'class' => 'items table-primary table table-bordered no-margin']); ?>
                <?= Html::openTag('tbody', ['id' => 'new_inv_item_row']); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['class' => 'td-text']); ?>
                            <?= Field::hidden($form, 'quote_id')->hideLabel(); ?>
                            <?= Html::openTag('div', ['class' => 'input-group', 'id' => 'product-quote']); ?>
                                <?php
                                    $optionsDataTask = [];
/**
 * @var App\Infrastructure\Persistence\Task\Task $task
 */
foreach ($tasks as $task) {
    $taskId = $task->reqId();
    $taskName = $task->getName();
    if (!empty($taskId) && null !== $taskName) {
        $optionsDataTask[$taskId] = $taskName;
    }
}
?>
                                <?= Field::select($form, 'task_id')
    ->optionsData($optionsDataTask)
    ->value(Html::encode($form->getTaskId())); ?>
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
    $taxRatePercent = $taxRate->getTaxRatePercent();
    $taxRatePercentNumber = $numberHelper->formatAmount($taxRatePercent);
    $taxRateName = $taxRate->getTaxRateName();
    if (null !== $taxRatePercentNumber && null !== $taxRateName) {
        $optionsDataTaxRate[$taxRateId] = $taxRatePercentNumber . '% - ' . $taxRateName;
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
                                'id'             => 'btn-quote-task-save',
                                'class'          => 'btn btn-info',
                                'data-bs-toggle' => 'tooltip',
                                'title'          => $translator->translate('add.task')]); ?>
                                <?= new I()->addClass('bi bi-plus-lg'); ?>
                                <?= $translator->translate('save'); ?>
                            <?= Html::closeTag('button'); ?>
                            <?= Html::openTag('span', [
                                'id'    => 'quote-task-saving',
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
                <?= Html::Tag('button', '', ['hidden' => 'hidden', 'class' => 'btn_quote_task_add_row btn btn-primary btn-md active bi bi-plus'])
                    ->content($translator->translate('add.new.row')); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?=  new Form()->close(); ?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div'); ?>
