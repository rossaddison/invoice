<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
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

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvRecurringForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>    
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>            
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('p'); ?>
                    <?= $translator->translate('invoice.recurring.original.invoice.date').'('.$dateHelper->display().')'; ?>
                    <?= $invDateCreated->format('Y-m-d'); ?>
                <?= Html::closeTag('p'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::hidden($form, 'inv_id')
                        ->hideLabel();
?>
                <?= Html::closeTag('div'); ?>       
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
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
                    <?=
    /**
     * Purpose: Changing this frequency will calculate the start date from the current (above) immutable invoice date
     * @see C:\wamp64\www\invoice\src\Invoice\Asset\rebuild-1.13\js\inv.js get_recur_start_date
     * @see C:\wamp64\www\invoice\src\Invoice\Asset\rebuild-1.13\js\inv.js $('#frequency').change(function () {
     */
    Field::select($form, 'frequency')
    ->label($translator->translate('invoice.recurring.frequency'))
    ->value($form->getFrequency() ?? '')
    ->disabled(true)
    ->optionsData($optionsDataFrequency);
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::hidden($form, 'start')
        ->hideLabel(true)
        ->label($translator->translate('i.start') ." (".  $dateHelper->display().") ")
        ->value(!is_string($start = $form->getStart()) ? $start?->format('Y-m-d') : '');
?>
                <?= Html::closeTag('div'); ?>                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'next')
    ->label($translator->translate('i.next') ." (".  $dateHelper->display().") ")
    ->value(!is_string($next = $form->getNext()) ? $next?->format('Y-m-d') : '')
    ->addInputAttributes([
        'data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('invoice.recurring.tooltip.next')
    ])
    ->readonly(true);
?>
                <?= Html::closeTag('div'); ?>                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'end')
        ->label($translator->translate('i.end') ." (".  $dateHelper->display().") ")
        ->value(!is_string($end = $form->getEnd()) ? $end?->format('Y-m-d') : '')
        ->readonly(true)
?>
                <?= Html::closeTag('div'); ?>
                <?= $button::backSave(); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close(); ?>