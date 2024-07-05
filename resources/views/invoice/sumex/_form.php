<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Sumex\SumexForm $form
 * @var App\Widget\Button $button 
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 * @var string $inv_id
 * @var string $title 
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataReasons
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('SumexForm')
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
    <?= $button::back_save(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::hidden($form, 'invoice')
                ->hideLabel()
                ->value($form->getInvoice() ?? ''); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::select($form, 'reason')
                    ->label($translator->translate('i.reason'))
                    ->optionsData($optionsDataReasons)
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'casenumber')
                ->label($translator->translate('i.case_number'))
                ->placeholder($translator->translate('i.case_number'))    
                ->value(Html::encode($form->getCasenumber() ?? ''))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>    
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::textarea($form, 'diagnosis')
                ->label($translator->translate('i.invoice_sumex_diagnosis'))
                ->placeholder($translator->translate('i.invoice_sumex_diagnosis'))    
                ->value(Html::encode($form->getDiagnosis() ?? ''))    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::textarea($form, 'observations')
                ->label($translator->translate('i.sumex_observations'))
                ->placeholder($translator->translate('i.sumex_observations'))    
                ->value(Html::encode($form->getObservations() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::date($form, 'treatmentstart')
                ->label($translator->translate('i.treatment_start'))
                ->value(Html::encode($form->getTreatmentstart() instanceof \DateTimeImmutable ? 
                             $form->getTreatmentstart()->format('Y-m-d') : (is_string(
                             $form->getTreatmentstart()) ? 
                             $form->getTreatmentstart() : '')))
                ->required(true)            
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::date($form, 'treatmentend')
                ->label($translator->translate('i.treatment_end'))
                ->value(Html::encode($form->getTreatmentend() instanceof \DateTimeImmutable ? 
                             $form->getTreatmentend()->format('Y-m-d') : (is_string(
                             $form->getTreatmentend()) ? 
                             $form->getTreatmentend() : '')))
                ->required(true)            
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::date($form, 'casedate')
                ->label($translator->translate('i.case_date'))
                ->value(Html::encode($form->getCasedate() instanceof \DateTimeImmutable ? 
                             $form->getCasedate()->format('Y-m-d') : (is_string(
                             $form->getCasedate()) ? 
                             $form->getCasedate() : '')))
                ->required(true)            
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>