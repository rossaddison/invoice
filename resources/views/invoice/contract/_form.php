<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
    <?= Html::encode($title); ?>
<?= Html::closeTag('h1'); ?>
<?=
    Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ContractForm')
    ->open()
?>

    <?= Field::errorSummary($form)
        ->errors($errors)
        ->header($translator->translate('invoice.client.error.summary'))
        ->onlyCommonErrors()
    ?>
    <?= Field::text($form, 'client_id')
        ->readonly(true)
        ->value(Html::encode($form->getClient_id()) ?? $client_id)
    ?>    
    <?= Field::text($form, 'reference')
       ->label($translator->translate('invoice.invoice.contract.reference'))
       ->addInputAttributes([
           'value' => Html::encode($form->getReference() ?? ''),
       ])
       ->required(true)
       ->hint($translator->translate('invoice.hint.this.field.is.required')); 
    ?>
    <?= Field::text($form, 'name')
        ->label($translator->translate('invoice.invoice.contract.name'))
        ->addInputAttributes([
            'value' => Html::encode($form->getName() ?? ''),                
        ])
        ->required(true)
        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
    ?>
    <?= Field::date($form, 'period_start')
        ->label($translator->translate('invoice.invoice.contract.period.start'))
        ->addInputAttributes([
            'role' => 'presentation',
            'autocomplete' => 'off',
        ])
        ->value(Html::encode(Html::encode($form->getPeriod_start() ? $form->getPeriod_end()->format('Y-m-d') : '')))
        ->required(true)            
        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
    ?>
    <?= Field::date($form, 'period_end')
        ->label($translator->translate('invoice.invoice.contract.period.end'))
        ->addInputAttributes([
            'autocomplete' => 'off',
        ])
        ->value(Html::encode(Html::encode(($form->getPeriod_end() ? $form->getPeriod_end()->format('Y-m-d') : ''))))
        ->required(true)
        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
    ?>

<?= Html::closeTag('h1'); ?>
<?= $button::back_save(); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
