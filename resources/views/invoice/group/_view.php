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
<?php echo Html::openTag('div', ['class' => 'tabbable tabs-below']); ?>

    <?php echo Html::openTag('div', ['class' => 'tab-content']); ?>
        
        <?php echo Html::openTag('div'); ?>
            <?php echo Field::text($form, 'name')
            ->label($translator->translate('name'))
            ->addInputAttributes([
                'class'    => 'form-control',
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ])
            ->value(Html::encode($form->getName()))
            ->placeholder($translator->translate('name'))
            ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?php echo Html::tag('br'); ?>
            <?php echo Field::text($form, 'identifier_format')
            ->label($translator->translate('identifier.format'))
            ->addInputAttributes([
                'class'    => 'form-control taggable',
                'id'       => 'identifier_format',
                'name'     => 'identifier_format',
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ])
            ->value(Html::encode($form->getIdentifier_format()))
            ->placeholder('INV-{{{id}}}')
            ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?php echo Field::text($form, 'left_pad')
            ->label($translator->translate('left.pad'))
            ->addInputAttributes([
                'class'    => 'form-control',
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ])
            ->value(Html::encode($form->getLeft_pad()) ?: '0')
            ->placeholder('0')
            ->hint($translator->translate('hint.this.field.is.required')); ?>
            <?php echo Html::tag('br'); ?>
            <?php echo Field::text($form, 'next_id')
            ->label($translator->translate('next.id'))
            ->addInputAttributes([
                'class'    => 'form-control',
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ])
            ->value(Html::encode($form->getNext_id()) ?: '1')
            ->placeholder('1')
            ->hint($translator->translate('hint.this.field.is.required')); ?>    
            <?php echo Html::tag('br'); ?>
            <?php echo Html::closeTag('div'); ?>         
            <?php echo $button::back(); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
