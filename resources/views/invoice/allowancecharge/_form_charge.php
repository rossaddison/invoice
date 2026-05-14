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
 * @var array $charges
 * @var array $taxRates
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataReason
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTax
 */

$row             = ['class' => 'row'];
$formGroup       = ['class' => 'mb-3 form-group'];
$formSwitch      = ['class' => 'form-check form-switch'];
$formControlHtml = ['class' => 'form-control form-control-lg'];
$headerbar       = ['id' => 'headerbar'];
$headerbarTitle  = ['class' => 'headerbar-title'];
$checkboxLabel   = ['class' => 'form-check-label fs-4'];

?>
<?= new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open() ?>
 <?= $button::backSave(); ?>
 <?= Html::openTag('div', $headerbar); ?>
  <?= Html::openTag('h1', $headerbarTitle); ?>
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
  <?= Html::openTag('div', $row); ?>
   <?= Html::openTag('div', $formSwitch); ?>
    <?= Field::checkbox($form, 'level')
        ->inputLabel($translator->translate('allowance.or.charge.level'))
        ->inputLabelAttributes($checkboxLabel)
        ->inputClass('form-check-input');
    ?>
   <?= Html::closeTag('div'); ?>
   <?= Html::openTag('div', $formGroup); ?>
    <?php
    $optionsDataReason = [];
    /**
     * @var string $value
     */
    foreach ($charges as $key => $value) {
        $optionsDataReason[$value[0]] = Html::encode(ucfirst((string) $key) . '--       ' . $value[0] . '--' . $value[1]);
    }
    ?>
    <?= Field::select($form, 'reason')
        ->label($translator->translate('allowance.or.charge.reason'))
        ->addInputAttributes([
            'class' => $formControlHtml['class'],
            'id' => 'reason',
        ])
        ->value(Html::encode($form->getReason() ?? ''))
        ->optionsData($optionsDataReason, true)
        ->prompt($translator->translate('none'))
        ->hint($translator->translate('hint.this.field.is.required'));
    ?>
   <?= Html::closeTag('div'); ?>
   <?= Html::openTag('div', $formGroup); ?>
    <?= Field::text($form, 'multiplier_factor_numeric')
        ->label($translator->translate('allowance.or.charge.multiplier.factor.numeric'))
        ->addInputAttributes([
            'placeholder' => $translator->translate('allowance.or.charge.multiplier.factor.numeric'),
            'class' => $formControlHtml['class'],
            'id' => 'multiplier_factor_numeric',
        ])
        ->value(Html::encode($form->getMultiplierFactorNumeric() ?? '20'))
        ->hint($translator->translate('hint.this.field.is.required'));
    ?>
   <?= Html::closeTag('div'); ?>
   <?= Html::openTag('div', $formGroup); ?>
    <?= Field::text($form, 'amount')
        ->label($translator->translate('allowance.or.charge.amount'))
        ->addInputAttributes([
            'placeholder' => $translator->translate('allowance.or.charge.amount'),
            'class' => $formControlHtml['class'],
            'id' => 'amount',
        ])
        ->value(Html::encode($form->getAmount() ?? ''))
        ->hint($translator->translate('hint.this.field.is.required'));
    ?>
   <?= Html::closeTag('div'); ?>
   <?= Html::openTag('div', $formGroup); ?>
    <?= Field::text($form, 'base_amount')
        ->label($translator->translate('allowance.or.charge.base.amount'))
        ->addInputAttributes([
            'placeholder' => $translator->translate('allowance.or.charge.base.amount'),
            'class' => $formControlHtml['class'],
            'id' => 'base_amount',
        ])
        ->value(Html::encode($form->getBaseAmount() ?? '1000'))
        ->hint($translator->translate('hint.this.field.is.required'));
    ?>
   <?= Html::closeTag('div'); ?>
   <?= Html::openTag('div', $formGroup); ?>
    <?php
    $optionsDataTax = [];
    /**
     * @var App\Infrastructure\Persistence\TaxRate\TaxRate $taxRate
     */
    foreach ($taxRates as $taxRate) {
        $taxRateId = $taxRate->reqId();
        $optionsDataTax[$taxRateId] = Html::encode(
            (string) $taxRateId
            . ':  '
            . (string) $taxRate->getTaxRateName()
            . ' '
            . (string) $taxRate->getTaxRatePercent()
        );
    }
    ?>
    <?= Field::select($form, 'tax_rate_id')
        ->label($translator->translate('tax.rate'))
        ->addInputAttributes([
            'class' => $formControlHtml['class'],
            'id' => 'tax_rate_id',
        ])
        ->value(Html::encode($form->getTaxRateId() ?? ''))
        ->optionsData($optionsDataTax, true)
        ->prompt($translator->translate('none'))
        ->hint($translator->translate('hint.this.field.is.required'));
    ?>
   <?= Html::closeTag('div'); ?>
  <?= Html::closeTag('div'); ?>
 <?= Html::closeTag('div'); ?>
<?= new Form()->close() ?>