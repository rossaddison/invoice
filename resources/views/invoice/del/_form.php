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
<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryLocationForm')
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
    <?= $button::back_save(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    // all properties
                    ->onlyCommonErrors()
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'date_created')
                    ->label($translator->translate('invoice.common.date.created'), ['form-label'])
                    ->value(Html::encode(($form->getDate_created())->format('Y-m-d') ?? ''))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.common.date.created'),
                        'readonly' => 'readonly' 
                    ])
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'date_modified')
                    ->label($translator->translate('invoice.common.date.modified'), ['form-label'])
                    ->value(Html::encode(($form->getDate_modified())->format('Y-m-d') ?? ''))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.common.date.modified'),
                        'readonly' => 'readonly'
                    ])
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'name')
                    ->label($translator->translate('i.name'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.name')
                    ])
                    ->value(Html::encode($form->getName() ?? ''))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'building_number')
                    ->label($translator->translate('invoice.delivery.location.building.number'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.delivery.location.building.number'),
                    ])
                    ->value(Html::encode($form->getBuildingNumber() ?? ''))
                ?>
            <?= Html::closeTag('div'); ?>    
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'address_1')
                    ->label($translator->translate('i.street_address'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.street_address')
                    ])
                    ->value(Html::encode($form->getAddress_1() ?? ''))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'address_2')
                    ->label($translator->translate('i.street_address_2'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.street_address_2'),
                        'value' => Html::encode($form->getAddress_2() ?? ''),
                    ])
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'city')
                    ->label($translator->translate('i.city'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.city'),
                        'value' => Html::encode($form->getCity() ?? ''),
                    ])
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'state')
                    ->label($translator->translate('i.state'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.state'),
                        'value' => Html::encode($form->getState() ?? ''),
                    ])
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'zip')
                    ->label($translator->translate('i.zip'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.zip'),
                        'value' => Html::encode($form->getZip() ?? ''),
                    ])
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'country')
                    ->label($translator->translate('i.country'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.country'),
                    ])
                    ->value(Html::encode($form->getCountry() ?? ''))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::a($translator->translate('invoice.delivery.location.global.location.number'),
                        'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-Delivery/cac-DeliveryLocation/cbc-ID/',
                        ['style'=>'text-decoration:none']); ?>
                <?= Field::text($form, 'global_location_number')
                    ->label($translator->translate('invoice.delivery.location.global.location.number'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.delivery.location.global.location.number'),
                        'value' => Html::encode($form->getGlobal_location_number() ?? ''),
                    ])
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?php
                    $optionsDataEAS = [];
                    /**
                     * @see src/Invoice/Helpers/Peppol/PeppolArrays.php function electronic_address_scheme
                     * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-Delivery/cac-DeliveryLocation/cbc-ID/
                     * @var int $key
                     * @var array $value
                     */
                    foreach ($electronic_address_scheme as $key => $value) {
                        $optionsDataEAS[$value['code']] = $value['code'] . str_repeat("-", 10) . $value['description'];
                    } 
                ?>
                <?= Html::a('EAS','https://docs.peppol.eu/poacc/upgrade-3/codelist/eas'); ?>
                <?= Field::select($form, 'electronic_address_scheme')
                    ->label($translator->translate('invoice.delivery.location.electronic.address.scheme'))
                    ->optionsData($optionsDataEAS)
                    ->hint($translator->translate('invoice.hint.this.field.is.not.required'));
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>