<?php
declare(strict_types=1);


use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \App\Invoice\Entity\GentorRelation $gentorRelation
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
    <?= $title; ?>
<?= Html::closeTag('h1'); ?>

<?=
    Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('GeneratorRelationForm')
    ->open();
?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('invoice.error.summary'))
    ->onlyCommonErrors()
?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?php
    $optionsDataGenerators = [];
    foreach ($generators as $generator)
    {
        $optionsDataGenerators[$generator->getGentor_id()] = $generator->getCamelcase_capital_name();
    }
    
    echo Field::select($form, 'gentor_id')
    ->label($translator->translate('invoice.generator.relation.form.entity.generator'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'gentor_id'
    ])
    ->prompt($translator->translate('i.none'))        
    ->optionsData($optionsDataGenerators)
    ->required(true)        
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?= Field::text($form, 'lowercasename')
    ->label($translator->translate('invoice.generator.relation.form.lowercase.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('invoice.generator.relation.form.lowercase.name'),
        'class' => 'form-control',
        'id' => 'lowercasename'
    ])
    ->value(Html::encode($form->getLowercase_name()))
    ->required(true)
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?= Field::text($form, 'camelcasename')
    ->label($translator->translate('invoice.generator.relation.form.camelcase.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('invoice.generator.relation.form.camelcase.name'),
        'class' => 'form-control',
        'id' => 'camelcasename'
    ])
    ->value(Html::encode($form->getCamelcase_name()))
    ->required(true)    
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?= Field::text($form, 'view_field_name')
    ->label($translator->translate('invoice.generator.relation.form.view.field.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('invoice.generator.relation.form.view.field.name'),
        'class' => 'form-control',
        'id' => 'view_field_name'
    ])
    ->value(Html::encode($form->getView_field_name()))
    ->required(true)    
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>
<?= Html::closeTag('div'); ?>

<?= $button::back_save(); ?>    
<?= Form::tag()->close(); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
