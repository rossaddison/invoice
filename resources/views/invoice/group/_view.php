<?php
declare(strict_types=1);


use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Group\GroupForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @var string $csrf
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= $translator->translate('invoice.group.form'); ?>
<?= Html::closeTag('h1'); ?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('GroupForm')
    ->open()
?>
<?= Html::openTag('div', ['class' => 'tabbable tabs-below']); ?>

    <?= Html::openTag('div', ['class' => 'tab-content']); ?>
        
        <?= Html::openTag('div'); ?>
            <?= Field::text($form, 'name')
                ->label($translator->translate('i.name'))
                ->addInputAttributes([
                    'class' => 'form-control',
                    'readonly' => 'readonly',
                    'disabled' => 'disabled'
                ])
                ->value(Html::encode($form->getName()))
                ->placeholder($translator->translate('i.name'))
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'identifier_format')
                ->label($translator->translate('i.identifier_format'))
                ->addInputAttributes([
                    'class' => 'form-control taggable',
                    'id' => 'identifier_format',
                    'name' => 'identifier_format',
                    'readonly' => 'readonly',
                    'disabled' => 'disabled'
                ])
                ->value(Html::encode($form->getIdentifier_format()))
                ->placeholder('INV-{{{id}}}')
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
            <?= Field::text($form, 'left_pad')
                ->label($translator->translate('i.left_pad'))
                ->addInputAttributes([
                    'class' => 'form-control',
                    'readonly' => 'readonly',
                    'disabled' => 'disabled'
                ])
                ->value(Html::encode($form->getLeft_pad()) ?: '0')
                ->placeholder('0')
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'next_id')
                ->label($translator->translate('i.next_id'))
                ->addInputAttributes([
                    'class' => 'form-control',
                    'readonly' => 'readonly',
                    'disabled' => 'disabled'
                ])
                ->value(Html::encode($form->getNext_id()) ?: '1')
                ->placeholder('1')
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>    
            <?= Html::tag('br'); ?>
            <?= Html::closeTag('div'); ?>         
            <?= $button::back(); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
