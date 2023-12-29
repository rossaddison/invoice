<?php

declare(strict_types=1); 

use App\Widget\Button;
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
    ->id('FromDropDownForm')
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
    <?= Button::back_save($translator); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyProperties(...['default_email', 'include', 'email'])    
                    ->onlyCommonErrors()
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::checkbox($form, 'include')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.from.include.in.dropdown'))
                ?>       
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::checkbox($form, 'default_email')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.from.default.in.dropdown'))
                ?>     
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::email($form, 'email')
                    ->label($translator->translate('invoice.from.email.address'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.email'),
                        'value' => Html::encode($form->getEmail() ?? ''),
                        'class' => 'form-control',
                        'id' => 'email',    
                    ])
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