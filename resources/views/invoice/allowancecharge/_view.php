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
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

$ac = 'allowance.or.charge.';

echo Html::openTag('h1');
 echo Html::encode($title);
echo Html::closeTag('h1');

echo  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open();
echo Html::openTag('div');
 echo Html::openTag('div', ['class' => 'mb-3']); //1
  echo Html::openTag('div', ['class' => 'row']); //2
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'identifier')
        ->addInputAttributes(['style' => 'background:lightblue'])
        ->label($translator->translate('allowance.or.charge'))
        ->value(Html::encode($form->getIdentifier() == true
        ? $translator->translate($ac . 'charge')
        : $translator->translate($ac . 'allowance')))
        ->readonly(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'form-check form-switch']); //3
    echo Field::checkbox($form, 'level')
        ->inputLabel($translator->translate($ac . 'level'))
        ->inputLabelAttributes(['class' => 'form-check-label fs-4'])
        ->inputClass('form-check-input');
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'reason_code')
        ->addInputAttributes(['style' => 'background:lightblue'])
        ->label($translator->translate($ac . 'reason.code'))
        ->value(Html::encode($form->getReasonCode() ?? ''))
        ->readonly(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'reason')
        ->addInputAttributes(['style' => 'background:lightblue'])
        ->label($translator->translate($ac . 'reason'))
        ->value(Html::encode($form->getReason() ?? ''))
        ->readonly(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'multiplier_factor_numeric')
        ->addInputAttributes(['style' => 'background:lightblue'])
        ->label($translator->translate($ac . 'multiplier.factor.numeric'))
        ->value(Html::encode($form->getMultiplierFactorNumeric() ?? ''))
        ->readonly(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'amount')
        ->addInputAttributes(['style' => 'background:lightblue'])
        ->label($translator->translate($ac . 'amount'))
        ->value(Html::encode($form->getAmount() ?? ''))
        ->readonly(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'base_amount')
        ->addInputAttributes(['style' => 'background:lightblue'])
        ->label($translator->translate($ac . 'base.amount'))
        ->value(Html::encode($form->getBaseAmount() ?? ''))
        ->readonly(true);
   echo Html::closeTag('div'); //3
  echo Html::closeTag('div'); //2
 echo Html::closeTag('div'); //1
echo Html::closeTag('div');
echo $button::back();
echo  new Form()->close();
