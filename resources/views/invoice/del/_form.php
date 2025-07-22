<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @see App\Invoice\DeliveryLocation\DeliveryLocationController function form
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
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments, $actionQueryParameters))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('DeliveryLocationForm')
    ->open(); ?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo $button::backSave(); ?>
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::errorSummary($form)
        ->errors($errors)
        ->header($translator->translate('error.summary'))
                    // all properties
        ->onlyCommonErrors();
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'date_created')
    ->label($translator->translate('common.date.created'))
    ->value(Html::encode($form->getDate_created()->format('Y-m-d')))
    ->addInputAttributes([
        'placeholder' => $translator->translate('common.date.created'),
        'readonly'    => 'readonly',
    ]);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'date_modified')
    ->label($translator->translate('common.date.modified'))
    ->value(Html::encode($form->getDate_modified()->format('Y-m-d')))
    ->addInputAttributes([
        'placeholder' => $translator->translate('common.date.modified'),
        'readonly'    => 'readonly',
    ]);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'name')
    ->label($translator->translate('name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('name'),
    ])
    ->value(Html::encode($form->getName() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'building_number')
    ->label($translator->translate('delivery.location.building.number'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('delivery.location.building.number'),
    ])
    ->value(Html::encode($form->getBuildingNumber() ?? ''));
?>
            <?php echo Html::closeTag('div'); ?>    
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'address_1')
                ->label($translator->translate('street.address'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('street.address'),
                ])
                ->value(Html::encode($form->getAddress_1() ?? ''))
                ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'address_2')
    ->label($translator->translate('street.address.2'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('street.address.2'),
        'value'       => Html::encode($form->getAddress_2() ?? ''),
    ])
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'city')
    ->label($translator->translate('city'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('city'),
        'value'       => Html::encode($form->getCity() ?? ''),
    ])
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'state')
    ->label($translator->translate('state'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('state'),
        'value'       => Html::encode($form->getState() ?? ''),
    ])
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'zip')
    ->label($translator->translate('zip'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('zip'),
        'value'       => Html::encode($form->getZip() ?? ''),
    ])
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'country')
    ->label($translator->translate('country'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('country'),
    ])
    ->value(Html::encode($form->getCountry() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Html::a(
                    $translator->translate('delivery.location.global.location.number'),
                    'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-Delivery/cac-DeliveryLocation/cbc-ID/',
                    ['style' => 'text-decoration:none'],
                ); ?>
                <?php echo Field::text($form, 'global_location_number')
    ->label($translator->translate('delivery.location.global.location.number'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('delivery.location.global.location.number'),
        'value'       => Html::encode($form->getGlobal_location_number() ?? ''),
    ])->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php
$optionsDataEAS = [];
/**
 * @see src/Invoice/Helpers/Peppol/PeppolArrays.php function electronic_address_scheme
 * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-Delivery/cac-DeliveryLocation/cbc-ID/
 *
 * @var int   $key
 * @var array $value
 */
foreach ($electronic_address_scheme as $key => $value) {
    $optionsDataEAS[(string) $value['code']] = (string) $value['code'].str_repeat('-', 10).(string) $value['description'];
}
?>
                <?php echo Html::a('EAS', 'https://docs.peppol.eu/poacc/upgrade-3/codelist/eas'); ?>
                <?php echo Field::select($form, 'electronic_address_scheme')
    ->label($translator->translate('delivery.location.electronic.address.scheme'))
    ->optionsData($optionsDataEAS)
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>