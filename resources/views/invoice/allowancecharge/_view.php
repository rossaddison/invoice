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
?>

<?= Html::openTag('h1'); ?>
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open(); ?>
<?= Html::openTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'identifier')
                    ->addInputAttributes(['style' => 'background:lightblue'])
                    ->label($translator->translate('allowance.or.charge'))
                    ->value(Html::encode($form->getIdentifier() == true
                    ? $translator->translate('allowance.or.charge.charge')
                    : $translator->translate('allowance.or.charge.allowance')))
                    ->readonly(true);
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'form-check form-switch']); ?>
                <?= Field::checkbox($form, 'level')
    ->inputLabel($translator->translate('allowance.or.charge.level')) // set the custom label here
    ->inputLabelAttributes(['class' => 'form-check-label fs-4'])
    ->inputClass('form-check-input')
?>
            <?= Html::closeTag('div'); ?>  
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'reason_code')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge.reason.code'))
    ->value(Html::encode($form->getReasonCode() ?? ''))
    ->readonly(true);
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'reason')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge.reason'))
    ->value(Html::encode($form->getReason() ?? ''))
    ->readonly(true);
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'multiplier_factor_numeric')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge.multiplier.factor.numeric'))
    ->value(Html::encode($form->getMultiplierFactorNumeric() ?? ''))
    ->readonly(true);
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'base_amount')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge.amount'))
    ->value(Html::encode($form->getAmount() ?? ''))
    ->readonly(true);
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'base_amount')
    ->addInputAttributes(['style' => 'background:lightblue'])
    ->label($translator->translate('allowance.or.charge.base.amount'))
    ->value(Html::encode($form->getBaseAmount() ?? ''))
    ->readonly(true);
?>
            <?= Html::closeTag('div'); ?>            
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= $button::back(); ?>
<?= Form::tag()->close(); ?>