<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Form;
use \DateTimeImmutable;

/**
 * @var App\Invoice\InvRecurring\InvRecurringForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper 
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Widget\Button $button
 * @var DateTimeImmutable $invDateCreated 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $disableNext
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvRecurringForm')
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
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('p'); ?>
                    <?= $translator->translate('invoice.recurring.original.invoice.date'); ?>
                    <?= $invDateCreated->format($dateHelper->style()); ?>
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
                        ->label($translator->translate('invoice.recurring.frequency') )
                        ->value($form->getFrequency() ?? '')
                        ->optionsData($optionsDataFrequency)    
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= A::tag()->href('https://wiki.invoiceplane.com/en/1.6/modules/recurring-invoices')->content('❔')?>
                    <?= Field::date($form, 'start')
                        ->label($translator->translate('i.start_date'))
                        ->value(!is_string($start = $form->getStart()) ? $start?->format('Y-m-d') : '');
                    ?>
                <?= Html::closeTag('div'); ?>            
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'next')  
                        ->label($translator->translate('i.next') ." (".  $dateHelper->display().") ")
                        ->value(!is_string($next = $form->getNext()) ? $next?->format('Y-m-d') : '')
                        // Always disabled because it is always the result of start + frequency
                        ->disabled(true)
                        ->addInputAttributes([                            
                            'data-bs-toggle' => 'tooltip',
                            'title' => $translator->translate('invoice.recurring.tooltip.next')                                
                        ])
                ?>
                <?= Html::closeTag('div'); ?>                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'end')
                        ->label($translator->translate('i.end_date') ."(".$translator->translate('i.optional'))
                        ->value(!is_string($end = $form->getEnd()) ? $end?->format('Y-m-d') : '')
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
