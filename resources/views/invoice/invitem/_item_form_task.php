<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\Helpers\NumberHelper       $numberHelper
 * @var App\Invoice\InvItem\InvItemForm        $form
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var App\Widget\Button                      $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var array                                  $tasks
 * @var array                                  $units
 * @var array                                  $taxRates
 * @var bool                                   $isRecurring
 * @var string                                 $alert
 * @var string                                 $csrf
 * @var string                                 $actionName
 * @var string                                 $title
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $error
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTask
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTaxRate
 */
$vat = '1' === $s->getSetting('enable_vat_registration') ? true : false;
?>
<?php echo Html::openTag('div', ['class' => 'panel panel-default']); ?>
    <?php echo Html::openTag('div', ['class' => 'panel-heading']); ?>
        <?php echo I::tag()
    ->addClass('bi bi-info-circle')
    ->addAttributes([
        'tooltip' => 'data-bs-toggle',
        'title'   => $s->isDebugMode(3),
    ])
    ->content(' '.$translator->translate('task'));
?>
    <?php echo Html::closeTag('div'); ?>    
    <?php echo Form::tag()
        ->post($urlGenerator->generate($actionName, $actionArguments))
        ->enctypeMultipartFormData()
        ->csrf($csrf)
        ->id('InvItemForm')
        ->open(); ?>
        
        <?php echo Html::openTag('div', ['class' => 'table-striped table-responsive']); ?>
            <?php echo Html::openTag('table', ['id' => 'item_table', 'class' => 'items table-primary table table-bordered no-margin']); ?>
                <?php echo Html::openTag('tbody', ['id' => 'new_inv_item_row']); ?>
                    <?php echo Html::openTag('tr'); ?>
                        <?php echo Html::openTag('td', ['rowspan' => '2', 'class' => 'td-icon']); ?>
                            <?php echo I::tag()
            ->addClass('fa fa-arrows cursor-move'); ?> 
                                <?php if ($isRecurring) { ?>
                                    <?php echo Html::tag('br'); ?>
                                        <?php echo I::tag()
                                    ->addAttributes([
                                        'title' => $translator->translate('recurring'),
                                        'class' => 'js-item-recurrence-toggler cursor-pointer fa fa-calendar-o text-muted',
                                    ]);
                                    ?>
                                        <?php echo Html::openTag('input', ['type' => 'hidden', 'name' => 'is_recurring', 'value' => '/']); ?>
                                <?php } ?>
                        <?php echo Html::closeTag('td'); ?>
                        <?php echo Html::openTag('td', ['class' => 'td-text']); ?>
                            <?php echo Field::hidden($form, 'inv_id')
                                        ->hideLabel(); ?>
                            <?php echo Field::hidden($form, 'id')
                                        ->hideLabel(); ?>
                            <?php echo Field::hidden($form, 'product_id')
                                        ->value('0')
                                        ->hideLabel(); ?>
                            <?php echo Html::openTag('div', ['class' => 'input-group', 'id' => 'task-no-product']); ?>    
                                <?php
                                    $optionsDataTask = [];
/**
 * @var App\Invoice\Entity\Task $task
 */
foreach ($tasks as $task) {
    $taskId   = $task->getId();
    $taskName = $task->getName() ?? '';
    if (!empty($taskId) && strlen($taskName) > 0) {
        $optionsDataTask[$taskId] = $taskName;
    }
}
?>
                                <?php echo Field::select($form, 'task_id')
    ->optionsData($optionsDataTask)
    ->value(Html::encode($form->getTask_id())); ?>
                            <?php echo Html::closeTag('div'); ?> 
                        <?php echo Html::closeTag('td'); ?>
                        <?php echo Html::openTag('td', ['class' => 'td-amount td-quality']); ?>
                            <?php echo Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php echo Field::number($form, 'quantity')
                            ->label($translator->translate('quantity'))
                            ->addInputAttributes(['class' => 'input-lg form-control amount has-feedback'])
                            ->value($numberHelper->format_amount($form->getQuantity()))
                            ->hint($translator->translate('hint.greater.than.zero.please'));
?>
                            <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::closeTag('td'); ?>
                        <?php echo Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?php echo Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php echo Field::text($form, 'price')
    ->label($translator->translate('price'))
    ->addInputAttributes(['class' => 'input-lg form-control amount has-feedback'])
    ->value($numberHelper->format_amount($form->getPrice() ?? 0.00))
    ->hint($translator->translate('hint.greater.than.zero.please')); ?>
                            <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::closeTag('td'); ?>
                        <?php echo Html::openTag('td', ['class' => 'td-amount']); ?>
                            <?php echo Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php echo Field::text($form, 'discount_amount')
    ->label($translator->translate('item.discount'))
    ->addInputAttributes([
        'class'          => 'input-lg form-control amount has-feedback',
        'data-bs-toggle' => 'tooltip',
        'data-placement' => 'bottom',
        'title'          => $s->getSetting('currency_symbol').' '.$translator->translate('per.item'),
    ])
    ->value($numberHelper->format_amount($form->getDiscount_amount() ?? 0.00)); ?>
                            <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::closeTag('td'); ?>                        
                        <?php echo Html::openTag('td', ['class' => 'td td-vert-middle']); ?>
                            <?php echo Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php
     $optionsDataTaxRate = [];
/**
 * @var App\Invoice\Entity\TaxRate $taxRate
 */
foreach ($taxRates as $taxRate) {
    $taxRateId       = $taxRate->getTaxRateId();
    $taxRatePercent  = $taxRate->getTaxRatePercent() ?? 0.00;
    $taxRateName     = $taxRate->getTaxRateName()    ?? '';
    $formattedNumber = $numberHelper->format_amount($taxRatePercent);
    if ((null !== $taxRateId) && ($taxRatePercent >= 0.00) && (strlen($taxRateName) > 0) && $formattedNumber >= 0.00) {
        $optionsDataTaxRate[$taxRateId] = (string) $formattedNumber.'% - '.$taxRateName;
    }
}
?>    
                                <?php echo Field::select($form, 'tax_rate_id')
                                    ->label(false === $vat ? $translator->translate('tax.rate') : $translator->translate('vat.rate'))
                                    ->addInputAttributes(['class' => 'form-control'])
                                    ->optionsData($optionsDataTaxRate)
                                    ->value(Html::encode($form->getTax_rate_id()))
                                    ->hint($translator->translate('hint.this.field.is.required'));
?>        
                            <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::closeTag('td'); ?>
                        <!-- see line 896 InvController: id modal-choose-items lies on views/product/modal_product_lookups_inv.php-->
                        <?php echo Html::openTag('td', ['class' => 'td td-vert-middle']); ?>
                            <?php echo Html::openTag('button', [
                                'type'           => 'submit',
                                'class'          => 'btn btn-info fa fa-plus',
                                'data-bs-toggle' => 'tooltip',
                                'title'          => 'invitem/edit_task']);
?>
                                <?php echo $translator->translate('save'); ?>
                            <?php echo Html::closeTag('button'); ?>
                        <?php echo Html::closeTag('td'); ?>              
                    <?php echo Html::closeTag('tr'); ?>
                    <?php echo Html::openTag('tr'); ?>
                        <?php echo Html::openTag('td', ['class' => 'td-textarea']); ?>
                            <?php echo Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php echo Field::textarea($form, 'description')
                        ->value(Html::encode($form->getDescription() ?? '')); ?>
                            <?php echo Html::closeTag('div'); ?>
                            <?php echo Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php echo Field::textarea($form, 'note')
                        ->value(Html::encode($form->getNote() ?? ''));
?>
                            <?php echo Html::closeTag('div'); ?>
                            <?php echo Html::openTag('div', ['class' => 'input-group']); ?>
                                <?php echo Field::text($form, 'order')
    ->value(Html::encode($form->getOrder() ?? ''));
?>
                            <?php echo Html::closeTag('div'); ?> 
                        <?php echo Html::closeTag('td'); ?>                            
                        <?php echo Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?php echo Html::openTag('span'); ?><?php echo $translator->translate('subtotal'); ?><?php echo Html::closeTag('span'); ?>
                                <?php echo Html::tag('br'); ?>    
                            <?php echo Html::openTag('span', ['name' => 'subtotal', 'class' => 'amount']); ?><?php echo Html::closeTag('span'); ?>        
                        <?php echo Html::closeTag('td'); ?>
                        <?php echo Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?php echo Html::openTag('span'); ?><?php echo false === $vat ? $translator->translate('discount') : $translator->translate('early.settlement.cash.discount'); ?><?php echo Html::closeTag('span'); ?>
                                <?php echo Html::tag('br'); ?>    
                            <?php echo Html::openTag('span', ['name' => 'discount_total', 'class' => 'amount']); ?><?php echo Html::closeTag('span'); ?>        
                        <?php echo Html::closeTag('td'); ?>
                        <?php echo Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?php echo Html::openTag('span', ['name' => 'charge_total']); ?><?php echo $translator->translate('item.charge'); ?><?php echo Html::closeTag('span'); ?>
                        <?php echo Html::closeTag('td'); ?>      
                        <?php echo Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?php echo Html::openTag('span'); ?><?php echo false === $vat ? $translator->translate('tax') : $translator->translate('vat.abbreviation'); ?><?php echo Html::closeTag('span'); ?>
                                <?php echo Html::tag('br'); ?>    
                            <?php echo Html::openTag('span', ['name' => 'tax_total', 'class' => 'amount']); ?><?php echo Html::closeTag('span'); ?>        
                        <?php echo Html::closeTag('td'); ?>        
                        <?php echo Html::openTag('td', ['class' => 'td-amount td-vert-middle']); ?>
                            <?php echo Html::openTag('span'); ?><?php echo $translator->translate('total'); ?><?php echo Html::closeTag('span'); ?>
                                <?php echo Html::tag('br'); ?>    
                            <?php echo Html::openTag('span', ['name' => 'total', 'class' => 'amount']); ?><?php echo Html::closeTag('span'); ?>        
                        <?php echo Html::closeTag('td'); ?>
                    <?php echo Html::closeTag('tr'); ?>        
                <?php echo Html::closeTag('tbody'); ?>
            <?php echo Html::closeTag('table'); ?>
        <?php echo Html::closeTag('div'); ?> 
        <?php echo Html::openTag('div', ['class' => 'col-xs-12 col-md-4']); ?>
            <?php echo Html::openTag('div', ['class' => 'btn-group']); ?>
                <?php echo Html::Tag('button', '', ['hidden' => 'hidden', 'class' => 'btn_inv_item_add_row btn btn-primary btn-md active bi bi-plus'])
            ->content($translator->translate('add.new.row')); ?>           
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Form::tag()->close(); ?>
    <?php echo Html::Tag('br'); ?>
<?php echo Html::closeTag('div'); ?>