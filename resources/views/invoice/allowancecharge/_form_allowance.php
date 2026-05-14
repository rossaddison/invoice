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

// ─── CSS shortcut variables ──────────────────────────────────────────────────
$row             = ['class' => 'row'];
$formGroup       = ['class' => 'mb-3 form-group'];
$formSwitch      = ['class' => 'form-check form-switch'];
$formControlHtml = ['class' => 'form-control form-control-lg'];
$headerbar       = ['id' => 'headerbar'];
$headerbarTitle  = ['class' => 'headerbar-title'];
$checkboxLabel   = ['class' => 'form-check-label fs-4'];
$ac = 'allowance.or.charge.';


echo new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open();
 echo $button::backSave();
 echo Html::openTag('div', $headerbar); //1
  echo Html::openTag('h1', $headerbarTitle); //2
   echo $title;
  echo Html::closeTag('h1'); //2
 echo Html::closeTag('div'); //1
 echo Html::openTag('div'); //1
  echo Field::errorSummary($form)
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
  echo Html::openTag('div', $row); //2
   echo Html::openTag('div', $formSwitch); //3
    echo Field::checkbox($form, 'level')
        ->inputLabel($translator->translate($ac . 'level'))
        ->inputLabelAttributes($checkboxLabel)
        ->inputClass('form-check-input');
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', $formGroup); //3
    $optionsDataReason = [];
    /**
     * @var string $value
     */
    foreach ($allowances as $value) {
        $optionsDataReason[$value] = Html::encode($value);
    }
    echo Field::select($form, 'reason')
        ->label($translator->translate($ac . 'reason'))
        ->addInputAttributes([
            'class' => $formControlHtml['class'],
            'id' => 'reason',
        ])
        ->value(Html::encode($form->getReason() ?? 'Discount'))
        ->optionsData($optionsDataReason, true)
        ->prompt($translator->translate('none'))
        ->hint($translator->translate('hint.this.field.is.required'));
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', $formGroup); //3
    echo Field::text($form, 'multiplier_factor_numeric')
        ->label($translator->translate($ac . 'multiplier.factor.numeric'))
        ->addInputAttributes([
            'placeholder' => $translator->translate($ac . 'multiplier.factor.numeric'),
            'class' => $formControlHtml['class'],
            'id' => 'multiplier_factor_numeric',
        ])
        ->value(Html::encode($form->getMultiplierFactorNumeric() ?? '20'))
        ->hint($translator->translate('hint.this.field.is.required'));
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', $formGroup); //3
    echo Field::text($form, 'amount')
        ->label($translator->translate($ac . 'amount'))
        ->addInputAttributes([
            'placeholder' => $translator->translate($ac . 'amount'),
            'class' => $formControlHtml['class'],
            'id' => 'amount',
        ])
        ->value(Html::encode($form->getAmount() ?? ''))
        ->hint($translator->translate('hint.this.field.is.required'));
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', $formGroup); //3
    echo Field::text($form, 'base_amount')
        ->label($translator->translate($ac . 'base.amount'))
        ->addInputAttributes([
            'placeholder' => $translator->translate($ac . 'base.amount'),
            'class' => $formControlHtml['class'],
            'id' => 'base_amount',
        ])
        ->value(Html::encode($form->getBaseAmount() ?? '1000'))
        ->hint($translator->translate('hint.this.field.is.required'));
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', $formGroup); //3
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
    echo Field::select($form, 'tax_rate_id')
        ->label($translator->translate('tax.rate'))
        ->addInputAttributes([
            'class' => $formControlHtml['class'],
            'id' => 'tax_rate_id',
        ])
        ->value(Html::encode($form->getTaxRateId() ?? ''))
        ->optionsData($optionsDataTax, true)
        ->prompt($translator->translate('none'))
        ->hint($translator->translate('hint.this.field.is.required'));
   echo Html::closeTag('div'); //3
  echo Html::closeTag('div'); //2
 echo Html::closeTag('div'); //1
echo new Form()->close();



