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

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= $translator->translate('i.delivery_form'); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryForm')
    ->open()
    ?> 
    <?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?= Html::encode($title) ?>
        <?= Html::closeTag('h1'); ?>
        <?= $button::back_save($translator); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
            <?=
                $date = $datehelper->get_or_set_with_style($form->getDate_created() ?? new \DateTimeImmutable('now'));
                Field::datetime($form, 'date_created')
                ->label($translator->translate('invoice.invoice.delivery.date.created') . ' (' . $datehelper->display() . ')')
                ->addInputAttributes([
                    'class' => 'form-control input-sm datepicker',
                    'id' => 'date_created',
                    'role' => 'presentation',
                    'autocomplete' => 'off'
                ])
                ->value($date)
                ->readonly(true)   
            ?>
            <?=
                $datem = $datehelper->get_or_set_with_style($form->getDate_modified() ?? new \DateTimeImmutable('now'));
                Field::datetime($form, 'date_modified')
                ->label($translator->translate('invoice.invoice.delivery.date.modified') . ' (' . $datehelper->display() . ')')
                ->addInputAttributes([
                    'class' => 'form-control input-sm datepicker',
                    'id' => 'date_modified',
                    'role' => 'presentation',
                    'autocomplete' => 'off'
                ])
                ->value($datem)
                ->readonly(true)   
            ?>
            <?=
                $sdate = $datehelper->get_or_set_with_style($form->getEnd_date() ?? new \DateTimeImmutable('now'));
                Field::datetime($form, 'start_date')
                ->label($translator->translate('invoice.invoice.delivery.start.date') . ' (' . $datehelper->display() . ')')
                ->addInputAttributes([
                     'class' => 'form-control input-sm datepicker',
                     'id' => 'start_date',
                     'role' => 'presentation',
                     'autocomplete' => 'off'
                ])
                ->value($sdate)
                ->readonly(true)    
            ?>
            <?=
                $datea = $datehelper->get_or_set_with_style($form->getActual_delivery_datae() ?? new \DateTimeImmutable('now'));
                Field::datetime($form, 'actual_delivery_date')
                ->label($translator->translate('invoice.invoice.delivery.actual.delivery.date') . ' (' . $datehelper->display() . ')')
                ->addInputAttributes([
                    'class' => 'form-control input-sm datepicker',
                    'id' => 'actual_delivery_date',
                    'role' => 'presentation',
                    'autocomplete' => 'off'
                ])
                ->value($datea)
                ->readonly(true)   
            ?>
            <?=
                $edate = $datehelper->get_or_set_with_style($form->getEnd_date() ?? new \DateTimeImmutable('now'));
                Field::datetime($form, 'end_date')
                ->label($translator->translate('invoice.invoice.delivery.end.date') . ' (' . $datehelper->display() . ')')
                ->addInputAttributes([
                     'class' => 'form-control input-sm datepicker',
                     'id' => 'end_date',
                     'role' => 'presentation',
                     'autocomplete' => 'off'
                ])
                ->value($edate)
                ->readonly(true)     
            ?>
            <?= Field::hidden($form, 'id')
                ->addInputAttributes([
                    'form-control',
                    'id' => 'id'
                ])
                ->value(Html::encode($form->getId()))
            ?>
            <?= Field::hidden($form, 'inv_id')
                ->addInputAttributes([
                    'form-control',
                    'id' => 'inv_id'
                ])
                ->value(Html::encode($form->getInv_id()))
            ?>
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
<?= Html::closeTag('form'); ?>
