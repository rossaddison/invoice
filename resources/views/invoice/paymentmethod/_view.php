<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\PaymentMethod\PaymentMethodForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */
?>

<?=  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('PaymentMethodForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'name')
                ->label($translator->translate('name'))
                ->addInputAttributes([
                    'class' => 'form-control  alert alert-warning',
                ])
                ->readonly(true)
                ->value(Html::encode($form->getName())); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::checkbox($form, 'active')
                    ->inputLabelAttributes(['class' => 'form-check-label'])
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('active')); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?=  new Form()->close() ?>
