<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @see App\Invoice\DeliveryLocation\DeliveryLocationController function view
 * @var App\Invoice\DeliveryLocation\DeliveryLocationForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $actionQueryParameters
 * @var array $electronic_address_scheme
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataEAS
 */
?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
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
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'date_created')
                    ->label($translator->translate('invoice.common.date.created'))
                    ->value(Html::encode(($form->getDate_created())->format('Y-m-d')))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.common.date.created'),
                        'readonly' => 'readonly' 
                    ])
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'date_modified')
                    ->label($translator->translate('invoice.common.date.modified'))
                    ->value(Html::encode(($form->getDate_modified())->format('Y-m-d')))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.common.date.modified'),
                        'readonly' => 'readonly'
                    ])
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'name')
                    ->label($translator->translate('i.name'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.name'),
                        'disabled' => 'disabled'
                    ])
                    ->value(Html::encode($form->getName() ?? ''))
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'building_number')
                    ->label($translator->translate('invoice.delivery.location.building.number'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('invoice.delivery.location.building.number'),
                        'disabled' => 'disabled'
                    ])
                    ->value(Html::encode($form->getBuildingNumber() ?? ''))
                ?>
            <?= Html::closeTag('div'); ?>    
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'address_1')
                    ->label($translator->translate('i.street_address'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.street_address'),
                        'disabled' => 'disabled'    
                    ])
                    ->value(Html::encode($form->getAddress_1() ?? '')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'address_2')
                    ->label($translator->translate('i.street_address_2'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.street_address_2'),
                        'disabled' => 'disabled',
                        'value' => Html::encode($form->getAddress_2() ?? ''),
                    ]); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'city')
                    ->label($translator->translate('i.city'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.city'),
                        'disabled' => 'disabled',
                        'value' => Html::encode($form->getCity() ?? ''),
                    ]); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'state')
                    ->label($translator->translate('i.state'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.state'),
                        'disabled' => 'disabled',
                        'value' => Html::encode($form->getState() ?? ''),
                    ]); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'zip')
                    ->label($translator->translate('i.zip'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.zip'),
                        'disabled' => 'disabled',
                        'value' => Html::encode($form->getZip() ?? ''),
                    ]); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Field::text($form, 'country')
                    ->label($translator->translate('i.country'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.country'),
                        'disabled' => 'disabled'
                    ])
                    ->value(Html::encode($form->getCountry() ?? '')); 
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
                        'disabled' => 'disabled',
                        'value' => Html::encode($form->getGlobal_location_number() ?? ''),
                    ]); 
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
                        $optionsDataEAS[(string)$value['code']] = (string)$value['code'] . str_repeat("-", 10) . (string)$value['description'];
                    } 
                ?>
                <?= Html::a('EAS','https://docs.peppol.eu/poacc/upgrade-3/codelist/eas'); ?>
                <?php Field::select($form, 'electronic_address_scheme')
                    ->label($translator->translate('invoice.delivery.location.electronic.address.scheme'))
                    ->optionsData($optionsDataEAS)
                    ->disabled(true);
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>