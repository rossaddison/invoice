<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\FromDropDown\FromDropDownForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
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
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::checkbox($form, 'include')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input disabled readonly')
                    ->ariaDescribedBy($translator->translate('invoice.from.include.in.dropdown'))
                ?>       
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::checkbox($form, 'default_email')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input disabled readonly')
                    ->ariaDescribedBy($translator->translate('invoice.from.default.in.dropdown'))
                ?>     
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::email($form, 'email')
                    ->label($translator->translate('invoice.from.email.address'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.email'),
                        'value' => Html::encode($form->getEmail() ?? ''),
                        'class' => 'form-control disabled readonly',
                        'id' => 'email'
                    ]) 
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
