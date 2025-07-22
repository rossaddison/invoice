<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\InvRecurring\InvRecurringForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Widget\Button $button
 * @var DateTimeImmutable $invDateCreated
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvRecurringForm')
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
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>            
            <?php echo Html::openTag('div'); ?>
                <?php echo Html::openTag('p'); ?>
                    <?php echo $translator->translate('recurring.original.invoice.date').'('.$dateHelper->display().')'; ?>
                    <?php echo $invDateCreated->format('Y-m-d'); ?>
                <?php echo Html::closeTag('p'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::hidden($form, 'inv_id')
                ->hideLabel();
?>
                <?php echo Html::closeTag('div'); ?>       
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php
    $optionsDataFrequency = [];
/**
 * @var string $key
 * @var string $value
 */
foreach ($numberHelper->recur_frequencies() as $key => $value) {
    $optionsDataFrequency[$key] = $translator->translate($value);
}
?> 
                    <?php echo
    /*
     * Purpose: Changing this frequency will calculate the start date from the current (above) immutable invoice date
     * @see C:\wamp64\www\invoice\src\Invoice\Asset\rebuild-1.13\js\inv.js get_recur_start_date
     * @see C:\wamp64\www\invoice\src\Invoice\Asset\rebuild-1.13\js\inv.js $('#frequency').change(function () {
     */
    Field::select($form, 'frequency')
        ->label($translator->translate('recurring.frequency'))
        ->value($form->getFrequency() ?? '')
        ->disabled(true)
        ->optionsData($optionsDataFrequency);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::hidden($form, 'start')
    ->hideLabel(true)
    ->label($translator->translate('start').' ('.$dateHelper->display().') ')
    ->value(!is_string($start = $form->getStart()) ? $start?->format('Y-m-d') : '');
?>
                <?php echo Html::closeTag('div'); ?>                
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::date($form, 'next')
                    ->label($translator->translate('next').' ('.$dateHelper->display().') ')
                    ->value(!is_string($next = $form->getNext()) ? $next?->format('Y-m-d') : '')
                    ->addInputAttributes([
                        'data-bs-toggle' => 'tooltip',
                        'title'          => $translator->translate('recurring.tooltip.next'),
                    ])
                    ->readonly(true);
?>
                <?php echo Html::closeTag('div'); ?>                
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::date($form, 'end')
                    ->label($translator->translate('end').' ('.$dateHelper->display().') ')
                    ->value(!is_string($end = $form->getEnd()) ? $end?->format('Y-m-d') : '')
                    ->readonly(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo $button::backSave(); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>