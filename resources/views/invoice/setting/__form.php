<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>

<h1><?= Html::encode($title) ?></h1>
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= $s->trans('settings_form'); ?>
<?= Html::closeTag('h1'); ?>
<?=
    Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('SettingForm')
    ->open()
?>
<?= Field::buttonGroup()
    ->addContainerClass('btn-group btn-toolbar float-end')
    ->buttonsData([
        [
            $translator->translate('invoice.cancel'),
            'type' => 'reset',
            'class' => 'btn btn-sm btn-danger',
            'name'=> 'btn_cancel'
        ],
        [
            $translator->translate('invoice.submit'),
            'type' => 'submit',
            'class' => 'btn btn-sm btn-primary',
            'name' => 'btn_send'
        ],
]) ?>
<?= 
    $alert; 
?>
<?= Html::openTag('div', ['class'=> 'card']); ?>
<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('invoice.setting.error.summary'))
    ->onlyProperties(...['setting_key', 'setting_value'])    
    ->onlyCommonErrors()
?>

<?= Field::text($form, 'setting_key')
    ->label($translator->translate('invoice.setting.key'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('invoice.setting.key'),
        'value' => Html::encode($form->getSetting_key() ?? ''),
        'class' => 'form-control',
        'id' => 'setting_key'
    ])
    ->required(true)
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>
<?= Field::text($form, 'setting_value')
    ->label($translator->translate('invoice.setting.value'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('invoice.setting.value'),
        'value' => Html::encode($form->getSetting_value() ?? ''),
        'class' => 'form-control',
        'id' => 'setting_value'
    ])
    ->required(true)
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

