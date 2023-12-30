<?php

declare(strict_types=1); 

use App\Widget\Button;
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

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= $translator->translate('invoice.invoice.delivery.add'); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryForm')
    ->open()
?> 
    <?= Field::buttonGroup()
    ->addContainerClass('btn-group btn-toolbar float-end')
    ->buttonsData([
        [
            $translator->translate('invoice.cancel'),
            'type' => 'reset',
            'class' => 'btn btn-sm btn-danger',
            'name'=> 'btn_cancel'
        ],
        [
            $translator->translate('invoice.submit'),
            'type' => 'submit',
            'class' => 'btn btn-sm btn-primary',
            'name' => 'btn_send'
        ],
    ]) ?>
    
    <?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::closeTag('div'); ?>    
            <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyProperties(...['date_created', 'date_modified'])    
                    ->onlyCommonErrors()
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
            <?php
                $date = $datehelper->get_or_set_with_style($form->getDate_created() ?? new \DateTimeImmutable('now'));
                Field::hidden($form, 'date_created')
                ->label($translator->translate('invoice.invoice.delivery.date.created') . ' (' . $datehelper->display() . ')')
                ->addInputAttributes([
                    'placeholder' => $translator->translate('invoice.invoice.delivery.date.created') . ' (' . $datehelper->display() . ')',
                    'id' => 'date_created',
                    'role' => 'presentation',
                    'autocomplete' => 'off'
                ])
                ->value($date)        
                ->hint($translator->translate('invoice.hint.this.field.is.not.required'));   
            ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php
                    $datem = $datehelper->get_or_set_with_style($form->getDate_modified() ?? new \DateTimeImmutable('now'));
                    Field::hidden($form, 'date_modified')
                    ->label($translator->translate('invoice.invoice.delivery.date.modified') . ' (' . $datehelper->display() . ')')
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.invoice.delivery.date.modified') . ' (' . $datehelper->display() . ')',
                        'id' => 'date_modified',
                        'role' => 'presentation',
                        'autocomplete' => 'off'
                    ])
                    ->value($datem)     
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required'));   
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php
                    echo Field::date($form, 'start_date')
                    ->label($translator->translate('invoice.invoice.delivery.start.date') . ' (' . $datehelper->display() . ')', ['class' => 'form-label'])
                    ->required(true)
                    ->value($form->getStart_date() ? ($form->getStart_date())->format('Y-m-d') : '')
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php
                    echo Field::date($form, 'actual_delivery_date')
                    ->label($translator->translate('invoice.invoice.delivery.actual.delivery.date') . ' (' . $datehelper->display() . ')')
                    ->required(true)
                    ->value($form->getStart_date() ? ($form->getActual_delivery_date())->format('Y-m-d') : '')        
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required'));   
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php
                    echo Field::date($form, 'end_date')
                    ->label($translator->translate('invoice.invoice.delivery.end.date') . ' (' . $datehelper->display() . ')', ['class' => 'form-label'])
                    ->required(true)
                    ->value($form->getEnd_date() ? ($form->getEnd_date())->format('Y-m-d') : '')
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php 
                    if ($del_count > 0) { 
                        $optionsDataDel = [];
                        foreach ($dels as $del) {
                            $optionsDataDel[$del->getId()] = $del->getAddress_1(). ', '.$del->getAddress_2() .', '. $del->getCity() .', '. $del->getZip();
                        }
                        echo Field::select($form, 'delivery_location_id')
                        ->label($translator->translate('invoice.invoice.delivery.location'),['class' => 'form-label'])    
                        ->addInputAttributes([
                            'class' => 'form-control',
                            'id' => 'delivery_location_id'
                        ])
                        ->optionsData($optionsDataDel)
                        ->value(Html::encode($form->getDelivery_location_id()));
                    } else {
                        echo Html::a($translator->translate('invoice.invoice.delivery.location.add'), $urlGenerator->generate('del/add',['client_id'=>$inv->getClient_id()]),['class'=>'btn btn-danger btn-lg mt-3']);
                    }    
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>
