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

<?=  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryForm')
    ->open()
?>
<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('delivery.add'); ?>
<?= Html::closeTag('h1'); ?>

    <?= Field::buttonGroup()
    ->addContainerClass('btn-group btn-toolbar float-end')
    ->buttonsData([
        [
            $translator->translate('cancel'),
            'type' => 'reset',
            'class' => 'btn btn-sm btn-danger',
            'name' => 'btn_cancel',
        ],
        [
            $translator->translate('submit'),
            'type' => 'submit',
            'class' => 'btn btn-sm btn-primary',
            'name' => 'btn_send',
        ],
    ]) ?>
    
            <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('error.summary'))
                    ->onlyProperties(...['date_created', 'date_modified'])
                    ->onlyCommonErrors()
            ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>    
            <?php
Field::hidden($form, 'date_created')
->label($translator->translate('delivery.date.created')
        . ' (' . $dateHelper->display() . ')')
->addInputAttributes([
    'placeholder' => $translator->translate('delivery.date.created')
        . ' (' . $dateHelper->display() . ')',
    'id' => 'date_created',
    'role' => 'presentation',
    'autocomplete' => 'off',
])
->value(!is_string($createdDate = $form->getDateCreated()) ?
        $createdDate->format('Y-m-d') : '')
->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>    
                <?php
        Field::hidden($form, 'date_modified')
        ->label($translator->translate('delivery.date.modified')
                . ' (' . $dateHelper->display() . ')')
        ->addInputAttributes([
            'placeholder' => $translator->translate('delivery.date.modified')
                . ' (' . $dateHelper->display() . ')',
            'id' => 'date_modified',
            'role' => 'presentation',
            'autocomplete' => 'off',
        ])
        ->value(!is_string($modifiedDate = $form->getDateModified()) ?
                $modifiedDate->format('Y-m-d') : '')
        ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>    
                <?php
    echo Field::date($form, 'start_date')
    ->label($translator->translate('delivery.start.date')
            . ' (' . $dateHelper->display() . ')')
    ->required(true)
    ->value(!is_string($startDate = $form->getStartDate()) ?
            $startDate->format('Y-m-d') : '')
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>    
                <?php
    echo Field::date($form, 'actual_delivery_date')
    ->label($translator->translate('delivery.actual.delivery.date')
            . ' (' . $dateHelper->display() . ')')
    ->required(true)
    ->value(!is_string($actualDeliveryDate = $form->getActualDeliveryDate())
            ? $actualDeliveryDate->format('Y-m-d') : '')
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>    
                <?php
    echo Field::date($form, 'end_date')
    ->label($translator->translate('delivery.end.date')
            . ' (' . $dateHelper->display() . ')')
    ->required(true)
    ->value(!is_string($endDate = $form->getEndDate()) ?
            $endDate->format('Y-m-d') : '')
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>    
                <?php
    if ($del_count > 0) {
        $optionsDataDel = [];
        /**
         * @var App\Invoice\Entity\DeliveryLocation $del
         */
        foreach ($dels as $del) {
            if (null !== $delId = $del->getId()) {
                $optionsDataDel[$delId] = ($del->getAddress1() ?? '')
                        . ', ' . ($del->getAddress2() ?? '')
                        . ', ' . ($del->getCity() ?? '')
                        . ', ' . ($del->getZip() ?? '');
            }
        }
        echo Field::select($form, 'delivery_location_id')
        ->label($translator->translate('delivery.location'))
        ->addInputAttributes([
            'class' => 'form-control',
            'id' => 'delivery_location_id',
        ])
        ->optionsData($optionsDataDel)
        ->value(Html::encode($form->getDeliveryLocationId()));
    } else {
        echo Html::a(
            $translator->translate('delivery.location.add'),
            $urlGenerator->generate(
                'del/add',
                ['client_id' => $inv->getClientId()],
                ['origin' => 'delivery',
                    'origin_id' => $currentRoute->getArgument('inv_id'),
                    'action' => 'add'],
            ),
            ['class' => 'btn btn-danger btn-lg mt-3'],
        );
    }
?>
        <?= Html::closeTag('div'); ?>
   
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>