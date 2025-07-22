<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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

<?php echo Html::openTag('h1'); ?><?php echo Html::encode($title); ?><?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?php echo $translator->translate('setting.form'); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('SettingForm')
    ->open();
?>
<?php echo $button::backSave(); ?>
<?php echo Html::openTag('div', ['class' => 'card']); ?>
<?php echo Field::text($form, 'setting_key')
    ->label($translator->translate('setting.key'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('setting.key'),
        'value'       => Html::encode($form->getSetting_key() ?? ''),
        'id'          => 'setting_key',
    ])
    ->disabled(true);
?>
<?php echo Field::text($form, 'setting_value')
    ->label($translator->translate('setting.value'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('setting.value'),
        'value'       => Html::encode($form->getSetting_value() ?? ''),
        'id'          => 'setting_value',
    ])
    ->disabled(true);
?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
