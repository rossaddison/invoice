<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Delivery\DeliveryForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $dels
 * @var int $del_count
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataDel
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
    ->post($urlGenerator->generate($actionName, $actionArguments))
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
                Field::hidden($form, 'date_created')
                ->label($translator->translate('invoice.invoice.delivery.date.created') . ' (' . $dateHelper->display() . ')')
                ->addInputAttributes([
                    'placeholder' => $translator->translate('invoice.invoice.delivery.date.created') . ' (' . $dateHelper->display() . ')',
                    'id' => 'date_created',
                    'role' => 'presentation',
                    'autocomplete' => 'off'
                ])
                ->value(!is_string($createdDate = $form->getDate_created()) ? $createdDate->format('Y-m-d') : '')        
                ->hint($translator->translate('invoice.hint.this.field.is.not.required'));   
            ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php
                    Field::hidden($form, 'date_modified')
                    ->label($translator->translate('invoice.invoice.delivery.date.modified') . ' (' . $dateHelper->display() . ')')
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.invoice.delivery.date.modified') . ' (' . $dateHelper->display() . ')',
                        'id' => 'date_modified',
                        'role' => 'presentation',
                        'autocomplete' => 'off'
                    ])
                    ->value(!is_string($modifiedDate = $form->getDate_modified()) ? $modifiedDate->format('Y-m-d') : '')     
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required'));   
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php
                    echo Field::date($form, 'start_date')
                    ->label($translator->translate('invoice.invoice.delivery.start.date') . ' (' . $dateHelper->display() . ')')
                    ->required(true)
                    ->value(!is_string($startDate = $form->getStart_date()) ? $startDate->format('Y-m-d') : '')
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php
                    echo Field::date($form, 'actual_delivery_date')
                    ->label($translator->translate('invoice.invoice.delivery.actual.delivery.date') . ' (' . $dateHelper->display() . ')')
                    ->required(true)
                    ->value(!is_string($actualDeliveryDate = $form->getActual_delivery_date()) ? $actualDeliveryDate->format('Y-m-d') : '')        
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required'));   
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php
                    echo Field::date($form, 'end_date')
                    ->label($translator->translate('invoice.invoice.delivery.end.date') . ' (' . $dateHelper->display() . ')')
                    ->required(true)
                    ->value(!is_string($endDate = $form->getEnd_date()) ? $endDate->format('Y-m-d') : '')
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                <?php 
                    if ($del_count > 0) { 
                        $optionsDataDel = [];
                        /**
                         * @var App\Invoice\Entity\DeliveryLocation $del
                         */
                        foreach ($dels as $del) {
                            if (null!==$delId = $del->getId()) {
                                $optionsDataDel[$delId] = ($del->getAddress_1() ?? ''). ', '. ($del->getAddress_2() ?? '') .', '. ($del->getCity() ?? '').', '. ($del->getZip() ?? '');
                            }
                        }
                        echo Field::select($form, 'delivery_location_id')
                        ->label($translator->translate('invoice.invoice.delivery.location'))    
                        ->addInputAttributes([
                            'class' => 'form-control',
                            'id' => 'delivery_location_id'
                        ])
                        ->optionsData($optionsDataDel)
                        ->value(Html::encode($form->getDelivery_location_id()));
                    } else {
                        echo Html::a($translator->translate('invoice.invoice.delivery.location.add'), 
                                $urlGenerator->generate('del/add',
                                        ['client_id'=>$inv->getClient_id()],
                                        ['origin' => 'delivery',
                                         'origin_id' => $currentRoute->getArgument('inv_id'),   
                                         'action' => 'add']),['class'=>'btn btn-danger btn-lg mt-3']);
                    }    
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>
