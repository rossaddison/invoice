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
 * @var array $taxRates
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataReason
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTax
 */

?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open() ?>
    <?= $button::backSave(); ?>
    <?= Html::openTag('div', ['id' => 'headerbar']); ?>    
        <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?= $title; ?>
        <?= Html::closeTag('h1'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?> 
        <?= Field::errorSummary($form)
            ->errors($errors)
            ->header($translator->translate('error.summary'))
            ->onlyProperties(...[
                'id',
                'reason',
                'multiplier_factor_numeric',
                'amount',
                'base_amount',
                'tax_rate_id'])
            ->onlyCommonErrors();
?>    
        <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?= Field::hidden($form, 'id')
            ->addInputAttributes([
                'class' => 'form-control',
            ])
            ->hideLabel()
            ->value(Html::encode($form->getId()));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'form-check form-switch']); ?>
            <?= Field::checkbox($form, 'level')
    ->inputLabel($translator->translate('allowance.or.charge.level')) // set the custom label here
    ->inputLabelAttributes(['class' => 'form-check-label fs-4'])
    ->inputClass('form-check-input')
?>
        <?= Html::closeTag('div'); ?>   
        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
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
    ->label($translator->translate('allowance.or.charge.reason'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'reason',
    ])
    ->value(Html::encode($form->getReason() ?? 'Discount'))
    ->optionsData($optionsDataReason, true)
    ->prompt($translator->translate('none'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?=
    Field::text($form, 'multiplier_factor_numeric')
    ->label($translator->translate('allowance.or.charge.multiplier.factor.numeric'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('allowance.or.charge.multiplier.factor.numeric'),
        'class' => 'form-control',
        'id' => 'multiplier_factor_numeric',
    ])
    ->value(Html::encode($form->getMultiplierFactorNumeric() ??  '20'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>   
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?=
    Field::text($form, 'amount')
    ->label($translator->translate('allowance.or.charge.amount'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('allowance.or.charge.amount'),
        'class' => 'form-control',
        'id' => 'amount',
    ])
    ->value(Html::encode($form->getAmount() ??  ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?=
    Field::text($form, 'base_amount')
    ->label($translator->translate('allowance.or.charge.base.amount'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('allowance.or.charge.base.amount'),
        'class' => 'form-control',
        'id' => 'base_amount',
    ])
    ->value(Html::encode($form->getBaseAmount() ??  '1000'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?php
    $optionsDataTax = [];
/**
 * @var App\Invoice\Entity\TaxRate $taxRate
 */
foreach ($taxRates as $taxRate) {
    $taxRateId = $taxRate->getTaxRateId();
    if (null !== $taxRateId) {
        $optionsDataTax[$taxRateId] = (string) $taxRateId
            . ':  '
            . (string) $taxRate->getTaxRateName()
            . ' '
            . (string) $taxRate->getTaxRatePercent();
    }
}
?>
            <?= Field::select($form, 'tax_rate_id')
    ->label($translator->translate('tax.rate'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'tax_rate_id',
    ])
    ->value($form->getTaxRateId() ?? '')
    ->optionsData($optionsDataTax, true)
    ->prompt($translator->translate('none'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>