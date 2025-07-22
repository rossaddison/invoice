<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('SumexForm')
    ->open(); ?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo $button::backSave(); ?>
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::hidden($form, 'invoice')
        ->hideLabel()
        ->value($form->getInvoice() ?? ''); ?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::select($form, 'reason')
        ->label($translator->translate('reason'))
        ->optionsData($optionsDataReasons)
        ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'casenumber')
    ->label($translator->translate('case.number'))
    ->placeholder($translator->translate('case.number'))
    ->value(Html::encode($form->getCasenumber() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>    
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::textarea($form, 'diagnosis')
                ->label($translator->translate('sumex.diagnosis'))
                ->placeholder($translator->translate('sumex.diagnosis'))
                ->value(Html::encode($form->getDiagnosis() ?? ''))
                ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::textarea($form, 'observations')
    ->label($translator->translate('sumex.observations'))
    ->placeholder($translator->translate('sumex.observations'))
    ->value(Html::encode($form->getObservations() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::date($form, 'treatmentstart')
    ->label($translator->translate('treatment.start'))
    ->value(Html::encode($form->getTreatmentstart() instanceof DateTimeImmutable ?
                 $form->getTreatmentstart()->format('Y-m-d') : (is_string(
                     $form->getTreatmentstart(),
                 ) ?
                 $form->getTreatmentstart() : '')))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::date($form, 'treatmentend')
    ->label($translator->translate('treatment.end'))
    ->value(Html::encode($form->getTreatmentend() instanceof DateTimeImmutable ?
                 $form->getTreatmentend()->format('Y-m-d') : (is_string(
                     $form->getTreatmentend(),
                 ) ?
                 $form->getTreatmentend() : '')))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::date($form, 'casedate')
    ->label($translator->translate('case.date'))
    ->value(Html::encode($form->getCasedate() instanceof DateTimeImmutable ?
                 $form->getCasedate()->format('Y-m-d') : (is_string(
                     $form->getCasedate(),
                 ) ?
                 $form->getCasedate() : '')))
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>