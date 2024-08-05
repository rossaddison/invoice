<?php

declare(strict_types=1); 


use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Generator\GeneratorForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $tables
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors 
 */

?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= $translator->translate('i.view'); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('GeneratorForm')
    ->open()
?>
<?= $button::back() ?>
<?= Html::openTag('div', ['class' => 'container']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?> 
            <?= Html::openTag('div',['class' => 'card-header']); ?>
                    <?= Html::openTag('h5'); ?>
                        <?= $translator->translate('invoice.generator.table'); ?>
                    <?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?> 
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'pre_entity_table'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
            <?= Html::openTag('div',['class' => 'card-header']); ?>
                    <?= Html::openTag('h5'); ?>
                        <?= $translator->translate('invoice.generator.namespace'); ?>
                    <?= Html::closeTag('h5'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
                    <?= Field::text($form, 'namespace_path'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?> 
            <?= Html::openTag('div',['class' => 'card-header']); ?>
                <?= Html::openTag('h5'); ?><?= $translator->translate('invoice.generator.controller.and.repository'); ?><?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>  
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'route_prefix'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'route_suffix'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'camelcase_capital_name'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'small_singular_name'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'small_plural_name'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'flash_include')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.flash.include')); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'created_include')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.created.include')); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'modified_include')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.modified.include')); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'updated_include')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.updated.include')); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::checkbox($form, 'deleted_include')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.generator.deleted.include')); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?> 
            <?= Html::openTag('div',['class' => 'card-header']); ?>
                <?= Html::openTag('h5'); ?><?= $translator->translate('invoice.generator.controller.path.layout'); ?><?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'controller_layout_dir'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col mb-3']); ?>
                <?= Field::text($form, 'controller_layout_dir_dot_path'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::closeTag('form'); ?>
