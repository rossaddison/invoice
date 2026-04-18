<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Client\ClientForm $form
 * @var App\Invoice\ClientCustom\ClientCustomForm $clientCustomForm
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var App\Widget\FormFields $formFields
 *
 * Related logic: see config\common\params.php 'cvH'
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 *
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $countries
 * @var array $customValues
 * @var array $customFields
 * @var array $clientCustomValues
 * @var int $postal_address_count
 * @var string $actionName
 * @var string $alert
 * @var string $csrf
 * @var string $datepicker_dropdown_locale_cldr
 * @var string $origin
 * @var string $selectedCountry
 * @var string $selectedLanguage
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<string,list<string>> $errorsCustom
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataGender
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientFrequencyDropdownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataPostalAddresses
 */

?>
<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('client.form'); ?>
<?= Html::closeTag('h1'); ?>
<?=
     new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ClientForm')
    ->open()
?>
<?=
    $s->getSetting('disable_flash_messages') == '0' ? $alert : '';
?>
<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('client.error.summary'))
    ->onlyProperties(...['client_name', 'client_surname', 'client_email', 'client_age'])
    ->onlyCommonErrors()
?>

<!-- Tab navigation -->
<?= Html::openTag('ul', ['class' => 'nav nav-tabs nav-fill', 'role' => 'tablist']); ?>

 <?= Html::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']); ?>
  <?= Html::tag('button', '<i class="bi bi-person-fill me-1"></i>' . $translator->translate('personal.information'),
    ['class' => 'nav-link active bg-primary text-white', 'id' => 'tab-personal',
     'data-bs-toggle' => 'tab', 'data-bs-target' => '#pane-personal',
     'type' => 'button', 'role' => 'tab'])->encode(false); ?>
 <?= Html::closeTag('li'); ?>

 <?= Html::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']); ?>
  <?= Html::tag('button', '<i class="bi bi-house-fill me-1"></i>' . $translator->translate('address'),
    ['class' => 'nav-link bg-success text-white', 'id' => 'tab-address',
     'data-bs-toggle' => 'tab', 'data-bs-target' => '#pane-address',
     'type' => 'button', 'role' => 'tab'])->encode(false); ?>
 <?= Html::closeTag('li'); ?>

 <?= Html::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']); ?>
  <?= Html::tag('button', '<i class="bi bi-telephone-fill me-1"></i>' . $translator->translate('contact.information'),
    ['class' => 'nav-link bg-info text-white', 'id' => 'tab-contact',
     'data-bs-toggle' => 'tab', 'data-bs-target' => '#pane-contact',
     'type' => 'button', 'role' => 'tab'])->encode(false); ?>
 <?= Html::closeTag('li'); ?>

 <?= Html::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']); ?>
  <?= Html::tag('button', '<i class="bi bi-graph-up me-1"></i>' . $translator->translate('demographics'),
    ['class' => 'nav-link bg-warning text-dark', 'id' => 'tab-demographics',
     'data-bs-toggle' => 'tab', 'data-bs-target' => '#pane-demographics',
     'type' => 'button', 'role' => 'tab'])->encode(false); ?>
 <?= Html::closeTag('li'); ?>

 <?= Html::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']); ?>
  <?= Html::tag('button', '<i class="bi bi-receipt me-1"></i>' . $translator->translate('tax.information'),
    ['class' => 'nav-link bg-danger text-white', 'id' => 'tab-tax',
     'data-bs-toggle' => 'tab', 'data-bs-target' => '#pane-tax',
     'type' => 'button', 'role' => 'tab'])->encode(false); ?>
 <?= Html::closeTag('li'); ?>

<?= Html::closeTag('ul'); ?>

<!-- Tab panes -->
<?= Html::openTag('div', ['class' => 'tab-content border border-top-0 rounded-bottom p-3']); ?>

 <!-- Personal Information -->
 <?= Html::openTag('div', ['class' => 'tab-pane active', 'id' => 'pane-personal', 'role' => 'tabpanel']); ?>
  <?= Html::openTag('div', ['class' => 'd-flex justify-content-end mb-2']); ?>
   <?= Field::checkbox($form, 'client_active')
    ->inputLabelAttributes([
        'class' => 'form-check-label',
        'data-bs-toggle' => 'tooltip',
        'onclick' => "return confirm(" . "'" . $translator->translate('client.deactivate.warning') . "');",
        'title' => $translator->translate('client.deactivate.warning'),
    ])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('client.active')); ?>
  <?= Html::closeTag('div'); ?>
  <?= $formFields->clientTitleSelect($form, [
    $translator->translate('client.title.mr'),
    $translator->translate('client.title.mrs'),
    $translator->translate('client.title.miss'),
    $translator->translate('client.title.doctor'),
    $translator->translate('client.title.professor'),
  ]); ?>
  <?= $formFields->clientTextField($form, 'client_name', 'client.name', true); ?>
  <?= $formFields->clientTextField($form, 'client_surname', 'client.surname', false); ?>
  <?= $formFields->clientEmailField($form); ?>
  <?= $formFields->clientTelephoneField($form, 'client_mobile', 'mobile'); ?>
  <?= $formFields->clientTextField($form, 'client_group', 'client.group', false); ?>
  <?= Field::select($form, 'client_frequency')
    ->label($translator->translate('client.frequency'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClientFrequency() ?? ''),
        'placeholder' => $translator->translate('client.frequency'),
        'class' => 'form-select',
    ])
    ->optionsData($optionsDataClientFrequencyDropdownFilter)
    ->required(false); ?>
  <?= $formFields->clientTextField($form, 'client_number', 'client.number', false); ?>
  <?php
    $languageOptions = [];
    /** @var string $language */
    foreach ($s->localeLanguageArray() as $language) {
        $languageOptions[$language] = ucfirst($language);
    }
  ?>
  <?= $formFields->clientLanguageSelect($form, $languageOptions, $selectedLanguage); ?>
 <?= Html::closeTag('div'); ?>

 <!-- Address -->
 <?= Html::openTag('div', ['class' => 'tab-pane', 'id' => 'pane-address', 'role' => 'tabpanel']); ?>
  <?= $formFields->clientTextField($form, 'client_address_1', 'street.address', false); ?>
  <?= $formFields->clientTextField($form, 'client_address_2', 'street.address.2', false); ?>
  <?= $formFields->clientTextField($form, 'client_building_number', 'client.building.number', false); ?>
  <?= $formFields->clientTextField($form, 'client_city', 'city', false); ?>
  <?= $formFields->clientTextField($form, 'client_state', 'state', false); ?>
  <?= $formFields->clientTextField($form, 'client_zip', 'zip', false); ?>
  <?php
    $countryOptions = [];
    /** @var string $country */
    foreach ($countries as $country) {
        $countryOptions[$country] = ucfirst($country);
    }
  ?>
  <?= $formFields->clientCountrySelect($form, $countryOptions, $selectedCountry); ?>
  <?php
    /** @var App\Invoice\Entity\CustomField $customField */
    foreach ($customFields as $customField):
        if ($customField->getLocation() !== 1) { continue; }
        $cvH->printFieldForForm($customField, $clientCustomForm, $translator, $urlGenerator, $clientCustomValues, $customValues);
    endforeach;
  ?>
 <?= Html::closeTag('div'); ?>

 <!-- Contact Information -->
 <?= Html::openTag('div', ['class' => 'tab-pane', 'id' => 'pane-contact', 'role' => 'tabpanel']); ?>
  <?= $formFields->clientTelephoneField($form, 'client_phone', 'phone'); ?>
  <?= $formFields->clientTelephoneField($form, 'client_fax', 'fax'); ?>
  <?= $formFields->clientUrlField($form); ?>
  <?= Html::openTag('div', ['class' => 'mb-3']); ?>
   <?= Html::label($translator->translate('client.postaladdress.available') . ': ', 'postaladdress_id'); ?>
   <?php if ($postal_address_count > 0 && $origin == 'edit'): ?>
    <?= Field::select($form, 'postaladdress_id')
        ->label($translator->translate('client.postaladdress.available'))
        ->required(false)
        ->addInputAttributes([
            'value' => Html::encode($form->getPostaladdressId() ?? ''),
            'class' => 'form-select alert alert-warning',
        ])
        ->optionsData($optionsDataPostalAddresses); ?>
   <?php endif; ?>
   <?php if ($postal_address_count === 0 && $origin == 'edit'): ?>
    <?= Html::a($translator->translate('client.postaladdress.add'),
        $urlGenerator->generate('postaladdress/add', ['client_id' => $client->reqId(), 'origin' => 'client']),
        ['class' => 'btn btn-warning btn-lg mt-3']); ?>
   <?php endif; ?>
  <?= Html::closeTag('div'); ?>
  <?php
    /** @var App\Invoice\Entity\CustomField $custom_field */
    foreach ($customFields as $custom_field):
        if ($custom_field->getLocation() !== 2) { continue; }
        $cvH->printFieldForForm($custom_field, $clientCustomForm, $translator, $urlGenerator, $clientCustomValues, $customValues);
    endforeach;
  ?>
 <?= Html::closeTag('div'); ?>

 <!-- Demographics -->
 <?= Html::openTag('div', ['class' => 'tab-pane', 'id' => 'pane-demographics', 'role' => 'tabpanel']); ?>
  <?= Field::select($form, 'client_gender')
    ->label($translator->translate('gender'))
    ->addInputAttributes(['class' => 'form-select'])
    ->optionsData($optionsDataGender)
    ->value(Html::encode($form->getClientGender() ?? 0)); ?>
  <?= Field::date($form, 'client_birthdate')
    ->label($translator->translate('birthdate'))
    ->addInputAttributes([
        'placeholder'  => $translator->translate('birthdate'),
        'class'        => 'form-control form-control-lg',
        'id'           => 'client_birthdate',
        'role'         => 'presentation',
        'autocomplete' => 'off',
        'onclick'      => 'this.showPicker()',
    ])
    ->value(Html::encode($form->getClientBirthdate() ?? ''))
    ->required(false); ?>
  <?= Field::number($form, 'client_age')
    ->label($translator->translate('client.age'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('client.age'),
        'value'       => Html::encode($form->getClientAge() ?? '18'),
        'class'       => 'form-control form-control-lg',
        'id'          => 'client_age',
    ])
    ->required(false)
    ->step(1); ?>
  <?php
    /** @var App\Invoice\Entity\CustomField $custom_field */
    foreach ($customFields as $custom_field):
        if ($custom_field->getLocation() !== 3) { continue; }
        $cvH->printFieldForForm($custom_field, $clientCustomForm, $translator, $urlGenerator, $clientCustomValues, $customValues);
    endforeach;
  ?>
 <?= Html::closeTag('div'); ?>

 <!-- Tax Information -->
 <?= Html::openTag('div', ['class' => 'tab-pane', 'id' => 'pane-tax', 'role' => 'tabpanel']); ?>
  <?= $formFields->clientTextField($form, 'client_vat_id', 'vat.id', false); ?>
  <?= $formFields->clientTextField($form, 'client_tax_code', 'tax.code', false); ?>
  <?php
    /** @var App\Invoice\Entity\CustomField $custom_field */
    foreach ($customFields as $custom_field):
        if ($custom_field->getLocation() !== 4) { continue; }
        $cvH->printFieldForForm($custom_field, $clientCustomForm, $translator, $urlGenerator, $clientCustomValues, $customValues);
    endforeach;
  ?>
  <?php if ($customFields): ?>
   <?= Html::openTag('div', ['class' => 'card mt-3']); ?>
    <?= Html::openTag('div', ['class' => 'card-header bg-secondary text-white']); ?>
     <?= $translator->translate('custom.fields'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'card-body']); ?>
     <?php
        /** @var App\Invoice\Entity\CustomField $custom_field */
        foreach ($customFields as $custom_field):
            if ($custom_field->getLocation() !== 0) { continue; }
            $cvH->printFieldForForm($custom_field, $clientCustomForm, $translator, $urlGenerator, $clientCustomValues, $customValues);
        endforeach;
     ?>
    <?= Html::closeTag('div'); ?>
   <?= Html::closeTag('div'); ?>
  <?php endif; ?>
 <?= Html::closeTag('div'); ?>

<?= Html::closeTag('div'); ?><!-- /tab-content -->

<?= $button::backSave(); ?>
<?= new Form()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
