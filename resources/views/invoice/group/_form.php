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

<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('invoice.error.summary'))
    ->onlyCommonErrors()
?>

<?= Html::openTag('div', ['class' => 'tabbable tabs-below']); ?>

    <?= Html::openTag('div', ['class' => 'tab-content']); ?>
        
        <?= Html::openTag('div'); ?>
            <?= Field::text($form, 'name')
                ->label($translator->translate('i.name'))
                ->addInputAttributes([
                    'class' => 'form-control'
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
                    'name' => 'identifier_format'
                ])
                ->value(Html::encode($form->getIdentifier_format()))
                ->placeholder('INV-{{{id}}}')
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
            <?= Field::text($form, 'left_pad')
                ->label($translator->translate('i.left_pad'))
                ->addInputAttributes([
                    'class' => 'form-control'
                ])
                ->value(Html::encode($form->getLeft_pad()) ?: '0')
                ->placeholder('0')
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>
            <?= Html::tag('br'); ?>
            <?= Field::text($form, 'next_id')
                ->label($translator->translate('i.next_id'))
                ->addInputAttributes([
                    'class' => 'form-control'
                ])
                ->value(Html::encode($form->getNext_id()) ?: '1')
                ->placeholder('1')
                ->hint($translator->translate('invoice.hint.this.field.is.required')); ?>    
            <?= Html::tag('br'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'form-group no-margin']); ?>
                <?= Html::openTag('label', ['for' => 'tags_client']);?>
                    <?= $translator->translate('i.identifier_format_template_tags'); ?>
                <?= Html::closeTag('label'); ?>
                <?= Html::openTag('p', ['class' => 'small']); ?>
                    <?= $translator->translate('i.identifier_format_template_tags_instructions'); ?>
                <?= Html::closeTag('p'); ?>
                <?= Html::openTag('div', ['class' => 'col-sm-6 col-md-4']); ?>
                    <?= Html::openTag('select', ['id' => 'tags_client', 'name' => 'tags_client', 'class' => 'tag-select form-control mb-3']); ?>    
                        <?= Html::openTag('option', ['value' => '{{{id}}}']); ?><?= $translator->translate('i.id'); ?><?= Html::closeTag('option'); ?>    
                        <?= Html::openTag('option', ['value' => '{{{year}}}']); ?><?= $translator->translate('i.current_year'); ?><?= Html::closeTag('option'); ?>
                        <?= Html::openTag('option', ['value' => '{{{yy}}}']); ?><?= $translator->translate('i.current_yy'); ?><?= Html::closeTag('option'); ?>
                        <?= Html::openTag('option', ['value' => '{{{month}}}']); ?><?= $translator->translate('i.current_month'); ?><?= Html::closeTag('option'); ?>
                        <?= Html::openTag('option', ['value' => '{{{day}}}']); ?><?= $translator->translate('i.current_day'); ?><?= Html::closeTag('option'); ?>
                    <?= Html::closeTag('select'); ?>
                <?= Html::closeTag('div'); ?>    
            <?= Html::closeTag('div'); ?>            
            <?= $button::backSave(); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>