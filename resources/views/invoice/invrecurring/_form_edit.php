<?php

declare(strict_types=1); 


use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>

<form id="InvRecurringForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">

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
                    <?= $translator->translate('invoice.recurring.original.invoice.date').'('.$datehelper->display().')'; ?>
                    <?= $immutable_invoice_date->format($datehelper->style()); ?>
                <?= Html::closeTag('p'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::hidden($form, 'inv_id')
                        ->hideLabel();
                    ?>
                <?= Html::closeTag('div'); ?>       
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php 
                        $optionsDataFrequency = [];
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
                        ->label($translator->translate('invoice.recurring.frequency'), ['class' => 'form-label'])
                        ->value($form->getFrequency() ?? '')
                        ->optionsData($optionsDataFrequency)    
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'start')
                        ->hideLabel(false)
                        ->addInputAttributes([
                            'readonly' => 'readonly'
                        ])
                        ->label($translator->translate('i.start') ." (".  $datehelper->display().") ", ['class' => 'form-label'])
                        ->value($form->getStart() ? ($form->getStart())->format('Y-m-d') : '')
                    ?>
                <?= Html::closeTag('div'); ?>                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'next')
                        ->label($translator->translate('i.next') ." (".  $datehelper->display().") ", ['class' => 'form-label'])
                        ->value($form->getNext() ? ($form->getNext())->format('Y-m-d') : '')
                        ->addInputAttributes([                            
                            'data-bs-toggle' => 'tooltip',
                            'title' => $translator->translate('invoice.recurring.tooltip.next')
                        ])
                ?>
                <?= Html::closeTag('div'); ?>                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'end')
                        ->label($translator->translate('i.end') ." (".  $datehelper->display().") ", ['class' => 'form-label'])
                        ->value($form->getEnd() ? ($form->getEnd())->format('Y-m-d') : '')
                ?>
                <?= Html::closeTag('div'); ?>
                <?= $button::back_save($translator); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close(); ?>
