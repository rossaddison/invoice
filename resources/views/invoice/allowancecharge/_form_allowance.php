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

?>
<?= new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open() ?>
 <?= $button::backSave(); ?>
 <?= Html::openTag('div', $headerbar); //0 ?>
  <?= Html::openTag('h1', $headerbarTitle); //1 ?>
   <?= $title; ?>
  <?= Html::closeTag('h1'); //1 ?>
 <?= Html::closeTag('div'); //0 ?>
 <?= Html::openTag('div'); //2 ?>
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
  <?= Html::openTag('div', $row); //3 ?>
   <?= Html::openTag('div', $formSwitch); //4 ?>
    <?= Field::checkbox($form, 'level')
        ->inputLabel($translator->translate('allowance.or.charge.level'))
        ->inputLabelAttributes($checkboxLabel)
        ->inputClass('form-check-input');
    ?>
   <?= Html::closeTag('div'); //4 ?>
   <?= Html::openTag('div', $formGroup); //5 ?>
    <?php
    $optionsDataReason = [];
    /**
     * @var string $value
     */
    foreach ($allowances as $value) {
        $optionsDataReason[$value] = Html::encode($value);
    }
    ?>
    <?= Field::select($form, 'reason')
        ->label($translator->translate('allowance.or.charge.reason'))
        ->addInputAttributes([
            'class' => $formControlHtml['class'],
            'id' => 'reason',
        ])
        ->value(Html::encode($form->getReason() ?? 'Discount'))
        ->optionsData($optionsDataReason, true)
        ->prompt($translator->translate('none'))
        ->hint($translator->translate('hint.this.field.is.required'));
    ?>
   <?= Html::closeTag('div'); //5 ?>
   <?= Html::openTag('div', $formGroup); //6 ?>
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
   <?= Html::closeTag('div'); //6 ?>
   <?= Html::openTag('div', $formGroup); //7 ?>
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
   <?= Html::closeTag('div'); //7 ?>
   <?= Html::openTag('div', $formGroup); //8 ?>
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
   <?= Html::closeTag('div'); //8 ?>
   <?= Html::openTag('div', $formGroup); //9 ?>
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
   <?= Html::closeTag('div'); //9 ?>
  <?= Html::closeTag('div'); //3 ?>
 <?= Html::closeTag('div'); //2 ?>
<?= new Form()->close() ?>
