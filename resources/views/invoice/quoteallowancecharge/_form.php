<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $acTemplateData
 * @var string $alert
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataAllowanceCharges
 */

$currencySymbol = $s->getSetting('currency_symbol');
$formControlLg  = ['class' => 'form-control form-control-lg'];
$ac = 'allowance.or.charge.';

echo new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('QuoteAllowanceChargeForm')
    ->open();
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
            <?= Html::encode($title) ?>
        <?= Html::closeTag('h1'); ?>
        <?= Html::openTag('div', ['id' => 'headerbar']); ?>
            <?= $button::backSave(); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'card-body']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                <?= Field::select($form, 'allowance_charge_id')
                    ->label($translator->translate($ac . 'allowance'))
                    ->addInputAttributes([
                        'class'             => $formControlLg['class'],
                        'id'                => 'allowance_charge_id',
                        'data-ac-templates' => json_encode($acTemplateData, JSON_THROW_ON_ERROR),
                    ])
                    ->optionsData($optionsDataAllowanceCharges)
                    ->value($form->getAllowanceChargeId())
                    ->prompt($translator->translate('none'))
                    ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div', ['id' => 'row-base-amount', 'style' => 'display:none']); ?>
                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                    <?= Html::label(
                        $translator->translate($ac . 'base.amount') . ' (' . $currencySymbol . ')',
                        'base_amount_calc'
                    )->class('form-label'); ?>
                    <?= Html::input('number', null, null, [
                        'id'          => 'base_amount_calc',
                        'class'       => $formControlLg['class'],
                        'min'         => '0',
                        'step'        => '0.01',
                        'placeholder' => $translator->translate($ac . 'base.amount'),
                    ]); ?>
                    <?= Html::tag('small', '', [
                        'id'    => 'amount-formula',
                        'class' => 'text-muted',
                    ]); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                <?= Field::text($form, 'amount')
                    ->label($translator->translate($ac . 'amount') . ' (' . $currencySymbol . ')')
                    ->addInputAttributes([
                        'class' => $formControlLg['class'],
                        'id'    => 'amount',
                        'step'  => '0.01',
                    ])
                    ->value(Html::encode($form->getAmount() ?? ''))
                    ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= new Form()->close(); ?>
