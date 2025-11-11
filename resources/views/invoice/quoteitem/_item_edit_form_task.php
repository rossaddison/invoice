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
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $tasks
 * @var array $taxRates
 * @var int $taxRateId
 * @var string $csrf
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTask
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTaxRate
 */

$vat = $s->getSetting('enable_vat_registration') === '1' ? true : false;
?>
<?= Html::openTag('div', ['class' => 'panel panel-default']); ?>
    <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
        <?= I::tag()
            ->addClass('bi bi-info-circle')
            ->addAttributes([
                'tooltip' => 'data-bs-toggle',
                'title' => $s->isDebugMode(12),
            ]);
?>
    <?= Html::closeTag('div'); ?>    
    <?= Form::tag()
->post($urlGenerator->generate($actionName, $actionArguments))
->enctypeMultipartFormData()
->csrf($csrf)
->id('QuoteItemFormEditTask')
->open() ?>
        
        <?= Html::openTag('div', ['class' => 'table-striped table-responsive']); ?>
            <?= Html::openTag('table', ['id' => 'item_table', 'class' => 'items table-primary table table-bordered no-margin']); ?>
                <?= Html::openTag('tbody', ['id' => 'edit_quote_item_row']); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['rowspan' => '2', 'class' => 'td-icon']); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td-text']); ?>
                            <?= Field::hidden($form, 'quote_id')
                                ->hideLabel(); ?>
                            <?= Html::openTag('div', ['class' => 'input-group', 'id' => 'product']); ?>    
                                <?php
                                    $optionsDataTask = [];
/**
 * @var App\Invoice\Entity\Task $task
 */
foreach ($tasks as $task) {
    $taskId = $task->getId();
    $taskName = $task->getName();
    if (!empty($taskId) && null !== $taskName) {
        $optionsDataTask[$taskId] = $taskName;
    }
}
?>
                                <?= Field::select($form, 'task_id')
    ->optionsData($optionsDataTask)
    ->value(Html::encode($form->getTask_id()));
?>
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
    ->hint($translator->translate('hint.greater.than.zero.please'));
?>
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
    ->value($numberHelper->format_amount($form->getDiscount_amount() ?? 0.00));
?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('td'); ?>
                        <?= Html::openTag('td', ['class' => 'td td-vert-middle']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php
    $optionsDataTaxRate = [];
/**
 * @var App\Invoice\Entity\TaxRate $taxRate
 */
foreach ($taxRates as $taxRate) {
    $taxRateId = $taxRate->getTaxRateId();
    $taxRatePercent = $taxRate->getTaxRatePercent();
    $taxRatePercentNumber = $numberHelper->format_amount($taxRatePercent);
    $taxRateName = $taxRate->getTaxRateName();
    // Only build the drop down item if all values are present
    if (null !== $taxRatePercentNumber && null !== $taxRateName && null !== $taxRateId) {
        $optionsDataTaxRate[$taxRateId] =  $taxRatePercentNumber . '% - ' . $taxRateName;
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
                                <!-- see QuoteController: id modal-choose-items lies on views/product/modal_product_lookups_quote.php-->
                                <?= Html::openTag('button', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-info',
                                    'data-bs-toggle' => 'tooltip',
                                    'title' => 'quoteitem/edit_task']);
?>
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
                                <?= Field::text($form, 'order')
    ->value(Html::encode($form->getOrder() ?? '')); ?>
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
                        <?= Html::openTag('td'); ?>
                        <?= Html::closeTag('td'); ?>        
                    <?= Html::closeTag('tr'); ?>        
                <?= Html::closeTag('tbody'); ?>
            <?= Html::closeTag('table'); ?>
        <?= Html::closeTag('div'); ?>
        <?=Html::openTag('div', ['class' => 'col-xs-12 col-md-4']); ?>
            <?= Html::openTag('div', ['class' => 'btn-group']); ?>
                <?= Html::Tag('button', '', ['hidden' => 'hidden', 'class' => 'btn_quote_item_add_row btn btn-primary btn-md active bi bi-plus'])
                    ->content($translator->translate('add.new.row')); ?>           
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Form::tag()->close(); ?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div'); ?>