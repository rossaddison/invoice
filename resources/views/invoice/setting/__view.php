<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Setting\SettingForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('invoice.setting.form'); ?>
<?= Html::closeTag('h1'); ?>
<?=
    Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('SettingForm')
    ->open()
?>
<?= $button::backSave(); ?>
<?= Html::openTag('div', ['class' => 'card']); ?>
<?= Field::text($form, 'setting_key')
    ->label($translator->translate('invoice.setting.key'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('invoice.setting.key'),
        'value' => Html::encode($form->getSetting_key() ?? ''),
        'id' => 'setting_key'
    ])
    ->disabled(true);
?>
<?= Field::text($form, 'setting_value')
    ->label($translator->translate('invoice.setting.value'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('invoice.setting.value'),
        'value' => Html::encode($form->getSetting_value() ?? ''),
        'id' => 'setting_value'
    ])
    ->disabled(true);
?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
