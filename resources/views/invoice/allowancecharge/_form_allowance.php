<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\AllowanceCharge\AllowanceChargeForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $allowances
 * @var array $tax_rates
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataReason
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTax
 */

?>
<?= Html::openTag('h1'); ?>
    <?= (Html::a($title,'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AllowanceCharge/',['class'=>'btn btn-primary'])); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open() ?>
    <?= $button::back_save(); ?>
    <?= Html::openTag('div', ['id' => 'headerbar']); ?>    
        <?= Html::openTag('h1',['class' => 'headerbar-title']); ?>
            <?= $title; ?>
        <?= Html::closeTag('h1'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?> 
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
                ->hideLabel()
                ->value(Html::encode($form->getId())); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?php
                $optionsDataReason = [];
                /**
                 * @var string $value
                 */
                foreach ($allowances as $key => $value) {
                    $optionsDataReason[$value] = $value;
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
                ->prompt($translator->translate('i.none'))
                ->hint($translator->translate('invoice.hint.this.field.is.required'));    
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb3 form-group']); ?>
            <?=
                Field::text($form, 'multiplier_factor_numeric')
                ->label($translator->translate('invoice.invoice.allowance.or.charge.multiplier.factor.numeric'))
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
                ->label($translator->translate('invoice.invoice.allowance.or.charge.amount'))
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
                ->label($translator->translate('invoice.invoice.allowance.or.charge.base.amount'))
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
                /**
                 * @var App\Invoice\Entity\TaxRate $tax_rate
                 */
                foreach ($tax_rates as $tax_rate) {
                    $taxRateId = $tax_rate->getTax_rate_id();
                    if (null!==$taxRateId) {
                        $optionsDataTax[$taxRateId] = $taxRateId
                            .':  '
                            . ($tax_rate->getTax_rate_name() ?? '')
                            . ' '
                            . ($tax_rate->getTax_rate_percent() ?? '');
                    }    
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
                ->prompt($translator->translate('i.none'))
                ->hint($translator->translate('invoice.hint.this.field.is.required'));    
            ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>