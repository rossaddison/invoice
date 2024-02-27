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

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ProjectForm')
    ->open() ?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back_save($translator); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))  
                    ->onlyCommonErrors()
                ?>
                <?php
                    $optionsDataClient = [];
                    foreach ($clients as $client) { 
                        $optionsDataClient[$client->getClient_id()] = $client->getClient_name() . ' '. $client->getClient_surname();                    
                    }
                    echo Field::select($form, 'client_id')
                    ->label($translator->translate('i.client'),['control-label'])
                    ->addInputAttributes([
                        'id' => 'client_id', 
                        'class' => 'form-control',
                    ])    
                    ->value(Html::encode($form->getClient_id() ?? ''))        
                    ->optionsData($optionsDataClient)
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'name')
                    ->label($translator->translate('i.project_name'), ['form-label'])
                    ->addInputAttributes([
                        'id' => 'name',
                        'class' => 'form-control',
                        'placeholder' => $translator->translate('i.project_name')
                    ])
                    ->value(Html::encode($form->getName() ?? ''))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>