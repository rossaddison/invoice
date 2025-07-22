<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?php echo $translator->translate('group.form'); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('GroupForm')
    ->open();
?> 

<?php echo Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('error.summary'))
    ->onlyCommonErrors();
?>

<?php echo Html::openTag('div', ['class' => 'tabbable tabs-below']); ?>

    <?php echo Html::openTag('div', ['class' => 'tab-content']); ?>
        
        <?php echo Html::openTag('div'); ?>
            <?php echo Field::text($form, 'name')
            ->label($translator->translate('name'))
            ->addInputAttributes([
                'class' => 'form-control',
            ])
            ->value(Html::encode($form->getName()))
            ->placeholder($translator->translate('name'))
            ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?php echo Html::tag('br'); ?>
            <?php echo Field::text($form, 'identifier_format')
            ->label($translator->translate('identifier.format'))
            ->addInputAttributes([
                'class' => 'form-control taggable',
                'id'    => 'identifier_format',
                'name'  => 'identifier_format',
            ])
            ->value(Html::encode($form->getIdentifier_format()))
            ->placeholder('INV-{{{id}}}')
            ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?php echo Field::text($form, 'left_pad')
            ->label($translator->translate('left.pad'))
            ->addInputAttributes([
                'class' => 'form-control',
            ])
            ->value(Html::encode($form->getLeft_pad()) ?: '0')
            ->placeholder('0')
            ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?php echo Html::tag('br'); ?>
            <?php echo Field::text($form, 'next_id')
            ->label($translator->translate('next.id'))
            ->addInputAttributes([
                'class' => 'form-control',
            ])
            ->value(Html::encode($form->getNext_id()) ?: '1')
            ->placeholder('1')
            ->hint($translator->translate('hint.this.field.is.required')); ?>    
            <?php echo Html::tag('br'); ?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'form-group no-margin']); ?>
                <?php echo Html::openTag('label', ['for' => 'tags_client']); ?>
                    <?php echo $translator->translate('identifier.format.template.tags'); ?>
                <?php echo Html::closeTag('label'); ?>
                <?php echo Html::openTag('p', ['class' => 'small']); ?>
                    <?php echo $translator->translate('identifier.format.template.tags.instructions'); ?>
                <?php echo Html::closeTag('p'); ?>
                <?php echo Html::openTag('div', ['class' => 'col-sm-6 col-md-4']); ?>
                    <?php echo Html::openTag('select', ['id' => 'tags_client', 'name' => 'tags_client', 'class' => 'tag-select form-control mb-3']); ?>    
                        <?php echo Html::openTag('option', ['value' => '{{{id}}}']); ?><?php echo $translator->translate('id'); ?><?php echo Html::closeTag('option'); ?>    
                        <?php echo Html::openTag('option', ['value' => '{{{year}}}']); ?><?php echo $translator->translate('current.year'); ?><?php echo Html::closeTag('option'); ?>
                        <?php echo Html::openTag('option', ['value' => '{{{yy}}}']); ?><?php echo $translator->translate('current.yy'); ?><?php echo Html::closeTag('option'); ?>
                        <?php echo Html::openTag('option', ['value' => '{{{month}}}']); ?><?php echo $translator->translate('current.month'); ?><?php echo Html::closeTag('option'); ?>
                        <?php echo Html::openTag('option', ['value' => '{{{day}}}']); ?><?php echo $translator->translate('current.day'); ?><?php echo Html::closeTag('option'); ?>
                    <?php echo Html::closeTag('select'); ?>
                <?php echo Html::closeTag('div'); ?>    
            <?php echo Html::closeTag('div'); ?>            
            <?php echo $button::backSave(); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>