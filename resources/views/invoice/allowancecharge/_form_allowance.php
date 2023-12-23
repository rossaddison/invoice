<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

?>
<?= Html::openTag('h1'); ?>
    <?= (Html::a($title,'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AllowanceCharge/',['class'=>'btn btn-primary'])); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open() ?>

    <?= Html::openTag('div', ['id' => 'headerbar']); ?>    
        <?= Html::openTag('h1',['class' => 'headerbar-title']); ?>
            <?= $s->trans('allowancecharges_form'); ?>
        <?= Html::closeTag('h1'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::buttonGroup()
            ->addContainerClass('btn-group btn-toolbar float-end')
            ->buttonsData([
                [
                    $translator->translate('invoice.cancel'),
                    'type' => 'reset',
                    'class' => 'btn btn-sm btn-danger',
                    'name'=> 'btn_cancel'
                ],
                [
                    $translator->translate('invoice.submit'),
                    'type' => 'submit',
                    'class' => 'btn btn-sm btn-primary',
                    'name' => 'btn_send'
                ],
        ]) ?>
       
        <?= Field::errorSummary($form)
            ->errors($errors)
            ->header($translator->translate('invoice.error.summary'))
            ->onlyProperties(...[
                'id',
                'reason',
                'multiplier_factor_numeric', 
                'amount', 
                'base_amount',
                'tax_rate_id'])     
            ->onlyCommonErrors();
        ?>    
        <?= Html::openTag('div',['class' => 'row']); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?= Field::hidden($form, 'id')
                ->addInputAttributes([
                    'class' => 'form-control'
                ])
                ->label('') 
                ->value(Html::encode($form->getId() ?? '')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?php
                $optionsDataReason = [];
                foreach ($allowances as $key => $value) {
                    $optionsDataReason[$value] = ucfirst((string)$key).' '.$value;
                }
            ?>
            <?= Field::select($form, 'reason')
                ->label($translator->translate('invoice.invoice.allowance.or.charge.reason'))    
                ->addInputAttributes([
                    'class' => 'form-control',
                    'id' => 'reason'
                ])
                ->value($form->getReason() ?? 'Discount')
                ->optionsData($optionsDataReason, true)
                ->prompt($s->trans('none'))
                ->hint($translator->translate('invoice.hint.this.field.is.required'));    
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?=
                Field::text($form, 'multiplier_factor_numeric')
                ->label($translator->translate('invoice.invoice.allowance.or.charge.multiplier.factor.numeric'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $translator->translate('invoice.invoice.allowance.or.charge.multiplier.factor.numeric'),
                    'class' => 'form-control',
                    'id' => 'multiplier_factor_numeric',
                ])
                ->value(Html::encode($form->getMultiplier_factor_numeric() ??  '20')) 
                // ->required(true) not necessary ... @see #[Required] in AllowanceChargeForm
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>   
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?=
                Field::text($form, 'amount')
                ->label($translator->translate('invoice.invoice.allowance.or.charge.amount'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $translator->translate('invoice.invoice.allowance.or.charge.amount'),
                    'class' => 'form-control',
                    'id' => 'amount',
                ])
                ->value(Html::encode($form->getAmount() ??  '')) 
                // ->required(true) not necessary ... @see #[Required] in AllowanceChargeForm
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?=
                Field::text($form, 'base_amount')
                ->label($translator->translate('invoice.invoice.allowance.or.charge.base.amount'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $translator->translate('invoice.invoice.allowance.or.charge.base.amount'),
                    'class' => 'form-control',
                    'id' => 'base_amount',
                ])
                ->value(Html::encode($form->getBase_amount() ??  '1000')) 
                // ->required(true) not necessary ... @see #[Required] in AllowanceChargeForm
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?php
                $optionsDataTax = [];
                foreach ($tax_rates as $tax_rate) {
                    $optionsDataTax[$tax_rate->getTax_rate_id()] = $tax_rate->getTax_rate_id().':  '.$tax_rate->getTax_rate_name()
                                                                   . ' '
                                                                   . $tax_rate->getTax_rate_percent();
                }
            ?>
            <?= Field::select($form, 'tax_rate_id')
                ->label($translator->translate('invoice.invoice.tax.rate'))    
                ->addInputAttributes([
                    'class' => 'form-control',
                    'id' => 'tax_rate_id'
                ])
                ->value($form->getTax_rate_id() ?? '')
                ->optionsData($optionsDataTax, true)
                ->prompt($s->trans('none'))
                ->hint($translator->translate('invoice.hint.this.field.is.required'));    
            ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>