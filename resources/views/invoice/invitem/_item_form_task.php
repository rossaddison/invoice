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
 * @var array $tasks
 * @var array $units
 * @var array $taxRates
 * @var bool $isRecurring
 * @var string $alert
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $error
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTask
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTaxRate
 * 
 */

$vat = $s->get_setting('enable_vat_registration') === '1' ? true : false;
?>
<?= Html::openTag('div', ['class' => 'panel panel-default']); ?>
    <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
        <?= I::tag()
            ->addClass('bi bi-info-circle')
            ->addAttributes([
                'tooltip' => 'data-bs-toggle', 
                'title' => $s->isDebugMode(3)
            ])
            ->content(' '.$translator->translate('invoice.task')); 
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
                                                'title' => $translator->translate('i.recurring'),
                                                'class' => 'js-item-recurrence-toggler cursor-pointer fa fa-calendar-o text-muted'
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
                            <?= Field::hidden($form, 'product_id')
                                ->value('0')
                                ->hideLabel(); ?>
                            <?= Html::openTag('div' , ['class' => 'input-group', 'id' => 'task-no-product']); ?>    
                                <?php
                                    $optionsDataTask = [];
                                    /**
                                     * @var App\Invoice\Entity\Task $task
                                     */
                                    foreach ($tasks as $task) 
                                    {
                                        $taskId = $task->getId();
                                        $taskName = $task->getName() ?? '';
                                        if (!empty($taskId) && strlen($taskName) > 0) {
                                            $optionsDataTask[$taskId] = $taskName;
                                        }
                                    }
                                ?>
                                <?= Field::select($form, 'task_id')   
                                    ->optionsData($optionsDataTask)    
                                    ->value(Html::encode($form->getTask_id())); ?>
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
                                    /**
                                     * @var App\Invoice\Entity\TaxRate $taxRate
                                     */
                                    foreach ($taxRates as $taxRate) 
                                    {
                                        $taxRateId = $taxRate->getTax_rate_id();
                                        $taxRatePercent = $taxRate->getTax_rate_percent() ?? 0.00;
                                        $taxRateName = $taxRate->getTax_rate_name() ?? '';
                                        $formattedNumber = $numberHelper->format_amount($taxRatePercent);
                                        if ((null!==$taxRateId) && ($taxRatePercent >= 0.00) && (strlen($taxRateName) > 0) && $formattedNumber >= 0.00) {
                                            $optionsDataTaxRate[$taxRateId] = (string)$formattedNumber . '% - ' . $taxRateName;
                                        }
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
                        <!-- see line 896 InvController: id modal-choose-items lies on views/product/modal_product_lookups_inv.php-->
                        <?= Html::openTag('td', ['class' => 'td td-vert-middle']); ?>
                            <?= Html::openTag('button', [
                                'type' => 'submit',
                                'class' => 'btn btn-info fa fa-plus',  
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'invitem/add_task']); 
                            ?>
                                <?= $translator->translate('i.save'); ?>
                            <?= Html::closeTag('button'); ?>
                        <?= Html::closeTag('td'); ?>              
                    <?= Html::closeTag('tr'); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::openTag('td', ['class' => 'td-textarea']); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::textarea($form, 'description')
                                    ->value($form->getDescription() ?? ''); ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::textarea($form, 'note')
                                    ->value($form->getNote() ?? ''); 
                                ?>
                            <?= Html::closeTag('div'); ?>
                            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                                <?= Field::text($form, 'order')
                                    ->value(Html::encode($form->getOrder() ?? '')); 
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
                            <?= Html::openTag('span', ['name' => 'charge_total']); ?><?= $translator->translate('invoice.invoice.item.charge') ?><?= Html::closeTag('span'); ?>
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
                <?= Html::Tag('button', '', ['hidden' => 'hidden', 'class' => 'btn_inv_item_add_row btn btn-primary btn-md active bi bi-plus'])
                    ->content($translator->translate('i.add_new_row')); ?>           
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Form::tag()->close(); ?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div'); ?>