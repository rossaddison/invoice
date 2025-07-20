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

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('delivery.form'); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryForm')
    ->open()
?> 
    <?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?= Html::encode($title) ?>
        <?= Html::closeTag('h1'); ?>
        <?= $button::back(); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
            <?php
            Field::hidden($form, 'date_created')
            ->label($translator->translate('delivery.date.created') . ' (' . $dateHelper->display() . ')')
            ->addInputAttributes([
                'placeholder' => $translator->translate('delivery.date.created') . ' (' . $dateHelper->display() . ')',
                'id' => 'date_created',
                'role' => 'presentation',
                'autocomplete' => 'off',
            ])
            ->value(!is_string($createdDate = $form->getDate_created()) ? $createdDate->format('Y-m-d') : '');
?>
            <?=
    Field::hidden($form, 'date_modified')
    ->label($translator->translate('delivery.date.modified') . ' (' . $dateHelper->display() . ')')
    ->addInputAttributes([
        'placeholder' => $translator->translate('delivery.date.modified') . ' (' . $dateHelper->display() . ')',
        'id' => 'date_modified',
        'role' => 'presentation',
        'autocomplete' => 'off',
    ])
    ->value(!is_string($modifiedDate = $form->getDate_modified()) ? $modifiedDate->format('Y-m-d') : '')
?>
            
            <?=
    Field::date($form, 'start_date')
    ->label($translator->translate('delivery.start.date') . ' (' . $dateHelper->display() . ')')
    ->required(true)
    ->value(!is_string($startDate = $form->getStart_date()) ? $startDate->format('Y-m-d') : '')
    ->readonly(true);
?>    
           
            <?=
    Field::date($form, 'actual_delivery_date')
    ->label($translator->translate('delivery.actual.delivery.date') . ' (' . $dateHelper->display() . ')')
    ->value(Html::encode(!is_string($actualDeliveryDate = $form->getActual_delivery_date()) ? $actualDeliveryDate->format('Y-m-d') : ''))
    ->hint($translator->translate('hint.this.field.is.not.required'))
    ->readonly(true);
?>
            <?=
    Field::date($form, 'end_date')
    ->label($translator->translate('delivery.end.date') . ' (' . $dateHelper->display() . ')')
    ->value(Html::encode(!is_string($endDate = $form->getEnd_date()) ? $endDate->format('Y-m-d') : ''))
    ->readonly(true)
?>
            <?= Field::hidden($form, 'id')
    ->addInputAttributes([
        'form-control',
        'id' => 'id',
    ])
    ->value(Html::encode($form->getId()))
?>
            <?= Field::hidden($form, 'inv_id')
    ->addInputAttributes([
        'form-control',
        'id' => 'inv_id',
    ])
    ->value(Html::encode($form->getInv_id()))
?>
            <?php
    if ($del_count > 0) {
        $optionsDataDel = [];
        /**
         * @var App\Invoice\Entity\DeliveryLocation $del
         */
        foreach ($dels as $del) {
            if (null !== $delId = $del->getId()) {
                $optionsDataDel[$delId] = ($del->getAddress_1() ?? '') . ', ' . ($del->getAddress_2() ?? '') . ', ' . ($del->getCity() ?? '') . ', ' . ($del->getZip() ?? '');
            }
        }
        echo Field::select($form, 'delivery_location_id')
        ->label($translator->translate('delivery.location'))
        ->addInputAttributes([
            'class' => 'form-control',
            'id' => 'delivery_location_id',
        ])
        ->optionsData($optionsDataDel)
        ->value(Html::encode($form->getDelivery_location_id()));
    } else {
        echo Html::a($translator->translate('delivery.location.add'), $urlGenerator->generate('del/add', ['client_id' => $inv->getClient_id()]), ['class' => 'btn btn-danger btn-lg mt-3']);
    }
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>
