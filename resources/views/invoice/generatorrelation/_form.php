<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\GeneratorRelation\GeneratorRelationForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $generators
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= $title; ?>
<?= Html::closeTag('h1'); ?>

<?=
     new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('GeneratorRelationForm')
    ->open();
?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('error.summary'))
    ->onlyCommonErrors()
?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?php
$optionsDataGenerators = [];
/**
 * @var App\Invoice\Entity\Gentor $generator
 */
foreach ($generators as $generator) {
    $optionsDataGenerators[$generator->getGentorId()] = $generator->getCamelcaseCapitalName();
}

echo Field::select($form, 'gentor_id')
->label($translator->translate('generator.relation.form.entity.generator'))
->addInputAttributes([
    'class' => 'form-control form-control-lg',
    'id' => 'gentor_id',
])
->prompt($translator->translate('none'))
->optionsData($optionsDataGenerators)
->required(true)
->hint($translator->translate('hint.this.field.is.required'));
?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?= Field::text($form, 'lowercasename')
    ->label($translator->translate('generator.relation.form.lowercase.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.relation.form.lowercase.name'),
        'class' => 'form-control form-control-lg',
        'id' => 'lowercasename',
    ])
    ->value(Html::encode($form->getLowercaseName()))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?= Field::text($form, 'camelcasename')
    ->label($translator->translate('generator.relation.form.camelcase.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.relation.form.camelcase.name'),
        'class' => 'form-control form-control-lg',
        'id' => 'camelcasename',
    ])
    ->value(Html::encode($form->getCamelcaseName()))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'col mb-3']); ?>
<?= Field::text($form, 'view_field_name')
    ->label($translator->translate('generator.relation.form.view.field.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('generator.relation.form.view.field.name'),
        'class' => 'form-control form-control-lg',
        'id' => 'view_field_name',
    ])
    ->value(Html::encode($form->getViewFieldName()))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
<?= Html::closeTag('div'); ?>

<?= $button::backSave(); ?>
<?=  new Form()->close(); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
