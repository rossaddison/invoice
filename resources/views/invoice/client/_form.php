<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Json\Json;

/**
 * @var App\Invoice\Client\ClientForm $form
 * @var App\Invoice\ClientCustom\ClientCustomForm $clientCustomForm
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var App\Widget\ClientFormFields $formFields
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
 * @var array $dwellingData
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
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataDwellings
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
<?php /**
 * Previously restricted via ->onlyProperties(...) to just
 * client_name/client_surname/client_email/client_age -- an incomplete
 * whitelist against the form's real validated fields (client_mobile's
 * E.164 Regex rule among them). A validation failure on any field
 * outside that list silently showed no error anywhere, with no visual
 * cue why the save appeared to do nothing. Found 2026-08-31 via a real
 * client whose stored client_mobile ('07777777777', no leading '+')
 * blocked every save of that client -- including unrelated fields like
 * postaladdress_id -- with zero indication why. Removed the
 * restriction so any validated field's error is visible.
 */ ?>
<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('client.error.summary'))
    ->onlyCommonErrors()
?>

<!-- Tab navigation -->
<?= Html::openTag('ul', ['class' => 'nav nav-tabs nav-fill', 'role' => 'tablist']); ?>

 <?= Html::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']); ?>
  <?= Html::tag('button', '<i class="bi bi-person-fill me-1"></i>'
          . $translator->translate('personal.information'),
    ['class' => 'nav-link active bg-primary text-white', 'id' => 'tab-personal',
     'data-bs-toggle' => 'tab', 'data-bs-target' => '#pane-personal',
     'type' => 'button', 'role' => 'tab'])->encode(false); ?>
 <?= Html::closeTag('li'); ?>

 <?= Html::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']); ?>
  <?= Html::tag('button', '<i class="bi bi-house-fill me-1"></i>'
          . $translator->translate('client.addresses'),
    ['class' => 'nav-link bg-success text-white', 'id' => 'tab-address',
     'data-bs-toggle' => 'tab', 'data-bs-target' => '#pane-address',
     'type' => 'button', 'role' => 'tab'])->encode(false); ?>
 <?= Html::closeTag('li'); ?>

 <?= Html::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']); ?>
  <?= Html::tag('button', '<i class="bi bi-telephone-fill me-1"></i>'
          . $translator->translate('client.details'),
    ['class' => 'nav-link bg-info text-white', 'id' => 'tab-details',
     'data-bs-toggle' => 'tab', 'data-bs-target' => '#pane-details',
     'type' => 'button', 'role' => 'tab'])->encode(false); ?>
 <?= Html::closeTag('li'); ?>

<?= Html::closeTag('ul'); ?>

<?php
  // Rarely-used fields sit behind a "More" toggle that opens itself when any
  // of them already holds a value or has a validation error, so nothing is
  // ever hidden from a user who needs it.
  $moreAddressOpen = ($form->client_address_2 ?? '') !== '' || ($form->client_state ?? '') !== ''
      || isset($errors['client_address_2']) || isset($errors['client_state']);
  $moreContactOpen = ($form->client_fax ?? '') !== '' || ($form->client_telegram_chat_id ?? '') !== ''
      || isset($errors['client_fax']) || isset($errors['client_telegram_chat_id']);
?>

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
  <?php
    $mobileDefaultRegionKey = array_search($form->client_country ?? $selectedCountry, $countries, true);
    $mobileDefaultRegion = $mobileDefaultRegionKey === false ? '' : (string) $mobileDefaultRegionKey;
  ?>
  <?= $formFields->clientTelephoneField($form, 'client_mobile', 'mobile', [
    'data-e164' => '1',
    'data-e164-default-region' => $mobileDefaultRegion,
  ]); ?>
  <?= $formFields->clientTextField($form, 'client_group', 'client.group', false); ?>
  <?= Field::select($form, 'client_frequency')
    ->label($translator->translate('client.frequency'))
    ->addInputAttributes([
        'value' => Html::encode($form->client_frequency ?? ''),
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

 <!-- Addresses: billing, delivery and (HomeCare) service address -->
 <?= Html::openTag('div', ['class' => 'tab-pane', 'id' => 'pane-address', 'role' => 'tabpanel']); ?>

  <!-- Billing / registered address -->
  <?= Html::openTag('div', ['class' => 'card mb-3']); ?>
   <?= Html::openTag('div', ['class' => 'card-header bg-success text-white']); ?>
    <?= $translator->translate('client.address.billing'); ?>
   <?= Html::closeTag('div'); ?>
   <?= Html::openTag('div', ['class' => 'card-body']); ?>
    <?= Html::tag('p', $translator->translate('client.address.billing.hint'), ['class' => 'text-muted small']); ?>
    <?= $formFields->clientTextField($form, 'client_address_1', 'street.address', false); ?>
    <?= $formFields->clientTextField($form, 'client_building_number', 'client.building.number', false); ?>
    <?= $formFields->clientTextField($form, 'client_city', 'city', false); ?>
    <?= $formFields->clientTextField($form, 'client_zip', 'zip', false); ?>
    <?php
      $countryOptions = [];
      /** @var string $country */
      foreach ($countries as $country) {
          $countryOptions[$country] = ucfirst($country);
      }
    ?>
    <?= $formFields->clientCountrySelect($form, $countryOptions, $selectedCountry); ?>
    <?= Html::tag('button', $translator->translate('client.address.more'), [
        'type' => 'button',
        'class' => 'btn btn-link p-0 mb-2',
        'data-bs-toggle' => 'collapse',
        'data-bs-target' => '#client-more-address',
        'aria-expanded' => $moreAddressOpen ? 'true' : 'false',
        'aria-controls' => 'client-more-address',
    ]); ?>
    <?= Html::openTag('div', ['id' => 'client-more-address', 'class' => 'collapse' . ($moreAddressOpen ? ' show' : '')]); ?>
     <?= $formFields->clientTextField($form, 'client_address_2', 'street.address.2', false); ?>
     <?= $formFields->clientTextField($form, 'client_state', 'state', false); ?>
    <?= Html::closeTag('div'); ?>
    <?php
      /** @var App\Infrastructure\Persistence\CustomField\CustomField $customField */
      foreach ($customFields as $customField):
          if ($customField->getLocation() !== 1) { continue; }
          $cvH->printFieldForForm($customField, $clientCustomForm, $translator, $urlGenerator, $clientCustomValues, $customValues);
      endforeach;
    ?>
   <?= Html::closeTag('div'); ?>
  <?= Html::closeTag('div'); ?>

  <!-- Delivery / correspondence address (Peppol postal address) -->
  <?= Html::openTag('div', ['class' => 'card mb-3']); ?>
   <?= Html::openTag('div', ['class' => 'card-header bg-warning text-dark']); ?>
    <?= $translator->translate('client.address.delivery'); ?>
   <?= Html::closeTag('div'); ?>
   <?= Html::openTag('div', ['class' => 'card-body']); ?>
    <?= Html::tag('p', $translator->translate('client.address.delivery.hint'), ['class' => 'text-muted small']); ?>
    <?= Html::openTag('div', ['class' => 'form-check mb-3']); ?>
     <?= Html::tag('input', '', [
         'type' => 'checkbox',
         'class' => 'form-check-input',
         'id' => 'client_delivery_same_as_billing',
         'checked' => $postal_address_count === 0,
     ]); ?>
     <?= Html::tag('label', $translator->translate('client.address.delivery.same'), [
         'class' => 'form-check-label',
         'for' => 'client_delivery_same_as_billing',
     ]); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['id' => 'client-delivery-fields']); ?>
  <?= /**
       * Anchor for the "fix it now" link Inv\Trait\Peppol's
       * friendlyPeppolExceptionMessage() appends to
       * PeppolBuyerPostalAddressNotFoundException's flash message
       * (client/edit/{id}#postaladdress_field) -- a distinct id from
       * whatever Field::select() below auto-generates for the
       * 'postaladdress_id' attribute itself, so the two never collide.
       * client-addresses.ts un-ticks "Same as billing" when the page is
       * opened on this anchor so the target is never hidden.
       */
      Html::openTag('div', ['class' => 'mb-3', 'id' => 'postaladdress_field']); ?>
   <?= Html::label($translator->translate('client.postaladdress.available') . ': ', 'postaladdress_id'); ?>
   <?php /**
    * $origin used to gate both branches here (`&& $origin == 'edit'`), so
    * reaching this same edit page via any other link -- most notably
    * postaladdress/index's own "No Postal address" link and the invoice
    * list's client-active icon, both of which pass origin=inv -- silently
    * hid this entire section, dropdown/add-button and all, with no
    * indication why. $origin has no other use in this file (only ever
    * read here). Found 2026-08-31: the two most direct paths to fixing a
    * missing postal address landed on a page where the fix control itself
    * was invisible.
    *
    * $client->hasIdentity() gates the select (there's nothing to pick
    * from a brand-new client anyway -- postal_address_count is always 0
    * on Add) and $client->reqId() below (its link needs a real id --
    * calling reqId() on an unpersisted Client throws
    * LogicException("Client not persisted"),
    * App\Infrastructure\Persistence\Trait\RequireId -- found 2026-08-31
    * the same day, one commit later, when removing the $origin check
    * above first exposed this unconditionally). The "Add" button itself
    * stays visible either way per explicit request -- disabled with an
    * explanation on Add rather than hidden, since a client with zero
    * postal addresses should always see *some* control here, matching
    * what's already shown once the client exists.
    */ ?>
   <?php if ($client->hasIdentity() && $postal_address_count > 0): ?>
    <?= Field::select($form, 'postaladdress_id')
        ->label($translator->translate('client.postaladdress.available'))
        ->required(false)
        ->addInputAttributes([
            'value' => Html::encode($form->postaladdress_id ?? ''),
            'class' => 'form-select alert alert-warning',
        ])
        ->optionsData($optionsDataPostalAddresses); ?>
   <?php endif; ?>
   <?php if ($postal_address_count === 0): ?>
    <?php if ($client->hasIdentity()): ?>
     <?= Html::a($translator->translate('client.postaladdress.add'),
         $urlGenerator->generate('postaladdress/add', ['client_id' => $client->reqId(), 'origin' => 'client']),
         ['class' => 'btn btn-warning btn-lg mt-3']); ?>
    <?php else: ?>
     <?php /**
      * A `title` on the disabled <a> itself never shows: pointer-events:none
      * blocks the hover that would trigger it. Bootstrap's own documented
      * fix for a disabled-link tooltip -- put data-bs-toggle="tooltip" +
      * title on a wrapping element that still receives pointer events
      * instead. initTooltips() (index.ts) already auto-initializes every
      * [data-bs-toggle="tooltip"] on the page, so no extra JS needed here.
      */ ?>
     <?= Html::openTag('span', [
         'data-bs-toggle' => 'tooltip',
         'title' => $translator->translate('client.postaladdress.add.save.first'),
     ]); ?>
      <?= Html::a($translator->translate('client.postaladdress.add'), '#', [
          'class' => 'btn btn-warning btn-lg mt-3 disabled',
          'style' => 'pointer-events:none',
          'aria-disabled' => 'true',
      ]); ?>
     <?= Html::closeTag('span'); ?>
    <?php endif; ?>
   <?php endif; ?>
  <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?><!-- /client-delivery-fields -->
   <?= Html::closeTag('div'); ?>
  <?= Html::closeTag('div'); ?>

  <!-- Service address (HomeCare Dwelling) -->
  <?= Html::openTag('div', ['class' => 'card mb-3']); ?>
   <?= Html::openTag('div', ['class' => 'card-header bg-info text-white']); ?>
    <?= $translator->translate('client.address.service'); ?>
   <?= Html::closeTag('div'); ?>
   <?= Html::openTag('div', [
       'class' => 'card-body',
       'id' => 'client-dwelling-picker',
       'data-dwellings' => Json::encode($dwellingData),
   ]); ?>
    <?= Html::tag('p', $translator->translate('client.address.service.hint'), ['class' => 'text-muted small']); ?>
    <?= Html::tag('input', '', [
        'type' => 'text',
        'id' => 'client_dwelling_search',
        'class' => 'form-control form-control-sm mb-2',
        'placeholder' => $translator->translate('client.dwelling.search'),
        'aria-label' => $translator->translate('client.dwelling.search'),
        'autocomplete' => 'off',
    ]); ?>
    <?= Field::select($form, 'dwelling_id')
        ->label($translator->translate('client.dwelling'))
        ->required(false)
        ->addInputAttributes([
            'value' => Html::encode($form->dwelling_id ?? ''),
            'class' => 'form-select',
        ])
        ->optionsData($optionsDataDwellings); ?>
    <?php if (isset($errors['dwelling_id'][0])): ?>
     <?= Html::tag('div', $errors['dwelling_id'][0], ['class' => 'text-danger small mb-2']); ?>
    <?php endif; ?>
    <?= Html::tag('div', '', ['id' => 'client_dwelling_summary', 'class' => 'text-muted small mb-2', 'aria-live' => 'polite']); ?>
    <?= Html::tag('button', $translator->translate('client.dwelling.use.as.billing'), [
        'type' => 'button',
        'id' => 'client_use_service_address',
        'class' => 'btn btn-outline-secondary btn-sm',
    ]); ?>
   <?= Html::closeTag('div'); ?>
  <?= Html::closeTag('div'); ?>

 <?= Html::closeTag('div'); ?>

 <!-- Contact, demographics and tax -->
 <?= Html::openTag('div', ['class' => 'tab-pane', 'id' => 'pane-details', 'role' => 'tabpanel']); ?>

  <?= Html::tag('h5', $translator->translate('contact.information'), ['class' => 'mb-3']); ?>
  <?= $formFields->clientTelephoneField($form, 'client_phone', 'phone'); ?>
  <?= $formFields->clientUrlField($form); ?>
  <?= Html::tag('button', $translator->translate('client.contact.more'), [
      'type' => 'button',
      'class' => 'btn btn-link p-0 mb-2',
      'data-bs-toggle' => 'collapse',
      'data-bs-target' => '#client-more-contact',
      'aria-expanded' => $moreContactOpen ? 'true' : 'false',
      'aria-controls' => 'client-more-contact',
  ]); ?>
  <?= Html::openTag('div', ['id' => 'client-more-contact', 'class' => 'collapse' . ($moreContactOpen ? ' show' : '')]); ?>
   <?= $formFields->clientTelephoneField($form, 'client_fax', 'fax'); ?>
   <?= Field::text($form, 'client_telegram_chat_id')
       ->label($translator->translate('client.telegram.chat.id'))
       ->addInputAttributes([
           'placeholder' => $translator->translate('client.telegram.chat.id'),
           'value' => Html::encode($form->client_telegram_chat_id ?? ''),
           'class' => 'form-control form-control-lg',
           'id' => 'client_telegram_chat_id',
       ])
       ->required(false)
       ->hint($translator->translate('hint.this.field.is.not.required')); ?>
  <?= Html::closeTag('div'); ?>
  <?php
    /** @var App\Infrastructure\Persistence\CustomField\CustomField $custom_field */
    foreach ($customFields as $custom_field):
        if ($custom_field->getLocation() !== 2) { continue; }
        $cvH->printFieldForForm($custom_field, $clientCustomForm, $translator, $urlGenerator, $clientCustomValues, $customValues);
    endforeach;
  ?>

  <?= Html::tag('h5', $translator->translate('demographics'), ['class' => 'mt-4 mb-3']); ?>
  <?= Field::select($form, 'client_gender')
    ->label($translator->translate('gender'))
    ->addInputAttributes(['class' => 'form-select'])
    ->optionsData($optionsDataGender)
    ->value(Html::encode($form->client_gender ?? 0)); ?>
  <?= Field::date($form, 'client_birthdate')
    ->label($translator->translate('birthdate'))
    ->addInputAttributes([
        'placeholder'  => $translator->translate('birthdate'),
        'class'        => 'form-control form-control-lg',
        'id'           => 'client_birthdate',
        'role'         => 'presentation',
        'autocomplete' => 'off',
        'data-action'      => 'show-picker',
    ])
    ->value(Html::encode($form->client_birthdate ?? ''))
    ->required(false); ?>
  <?= Field::number($form, 'client_age')
    ->label($translator->translate('client.age'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('client.age'),
        'value'       => Html::encode($form->client_age ?? '18'),
        'class'       => 'form-control form-control-lg',
        'id'          => 'client_age',
    ])
    ->required(false)
    ->step(1); ?>
  <?php
    /** @var App\Infrastructure\Persistence\CustomField\CustomField $custom_field */
    foreach ($customFields as $custom_field):
        if ($custom_field->getLocation() !== 3) { continue; }
        $cvH->printFieldForForm($custom_field, $clientCustomForm, $translator, $urlGenerator, $clientCustomValues, $customValues);
    endforeach;
  ?>

  <?= Html::tag('h5', $translator->translate('tax.information'), ['class' => 'mt-4 mb-3']); ?>
  <?= $formFields->clientTextField($form, 'client_vat_id', 'vat.id', false); ?>
  <?= $formFields->clientTextField($form, 'client_tax_code', 'tax.code', false); ?>
  <?php
    /** @var App\Infrastructure\Persistence\CustomField\CustomField $custom_field */
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
        /** @var App\Infrastructure\Persistence\CustomField\CustomField $custom_field */
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
