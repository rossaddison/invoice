<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Client\ClientForm $form
 * @var App\Invoice\ClientCustom\ClientCustomForm $clientCustomForm
 * @var App\Invoice\Entity\Client $client
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
<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('client.form'); ?>
<?= Html::closeTag('h1'); ?>
<?=
    Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ClientForm')
    ->open()
?>
<?=
    $alert;
?>
<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('client.error.summary'))
    ->onlyProperties(...['client_name', 'client_surname', 'client_email', 'client_age'])
    ->onlyCommonErrors()
?>
<?=
    Html::openTag('div', ['class' => 'card']),
Html::openTag('div', ['class' => 'card-header d-flex justify-content-between']),
$translator->translate('personal.information'),
Html::openTag('div', ['class' => 'p-2']),
Field::checkbox($form, 'client_active')
->inputLabelAttributes(['class' => 'form-check-label'])
->inputClass('form-check-input')
->ariaDescribedBy($translator->translate('client.active')),
Html::closeTag('div'),
Html::closeTag('div'),
Html::closeTag('div')
?>

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
        'value' => Html::encode($form->getClient_frequency() ?? ''),
        'placeholder' => $translator->translate('client.frequency'),
        'class' => 'form-control',
    ])
    ->optionsData($optionsDataClientFrequencyDropdownFilter)
    ->required(false);
?>

<?= $formFields->clientTextField($form, 'client_number', 'client.number', false); ?>  
    
<?php
    $languageOptions = [];
/** @var string $language */
foreach ($s->locale_language_array() as $language) {
    $languageOptions[$language] = ucfirst($language);
}
?>
<?= $formFields->clientLanguageSelect($form, $languageOptions, $selectedLanguage); ?>  

<?= Html::Tag('br'); ?>
<?= Html::openTag('div', ['class' => 'card']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?= $translator->translate('address'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= $formFields->clientTextField($form, 'client_address_1', 'street.address', true); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::tag('br'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= $formFields->clientTextField($form, 'client_address_2', 'street.address.2', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= $formFields->clientTextField($form, 'client_building_number', 'client.building.number', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= $formFields->clientTextField($form, 'client_city', 'city', true); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= $formFields->clientTextField($form, 'client_state', 'state', true); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= $formFields->clientTextField($form, 'client_zip', 'zip', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php
                $countryOptions = [];
/** @var string $country */
foreach ($countries as $cldr => $country) {
    $countryOptions[$country] = ucfirst($country);
}
?>
            <?= $formFields->clientCountrySelect($form, $countryOptions, $selectedCountry); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php
    /**
     * @var App\Invoice\Entity\CustomField $customField
     */
    foreach ($customFields as $customField): ?>
                <?php
    if ($customField->getLocation() !== 1) {
        continue;
    }
        ?>
                <?php $cvH->print_field_for_form($customField, $clientCustomForm, $translator, $clientCustomValues, $customValues); ?>
            <?php endforeach; ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::Tag('br'); ?>
<?= Html::openTag('div', ['class' => 'card']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?= $translator->translate('contact.information'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= $formFields->clientTelephoneField($form, 'client_phone', 'phone'); ?>
            <?= $formFields->clientTelephoneField($form, 'client_fax', 'fax'); ?>            
            <?= $formFields->clientUrlField($form); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
           <?= Html::openTag('div'); ?>
                <label for="postaladdress_id"><?= $translator->translate('client.postaladdress.available'); ?>: </label>
            <?= Html::closeTag('div'); ?>           
            <?= Html::openTag('div', ['class' => 'input-group']); ?>
                <?php
        // only allow the postal address add button if we are editing the client
        // we add a client on the dashboard
        if ($postal_address_count > 0 && $origin == 'edit') { ?>
                    <?= Field::select($form, 'postaladdress_id')
            ->label($translator->translate('client.postaladdress.available'))
            ->required(true)
            ->addInputAttributes([
                'value' => Html::encode($form->getPostaladdress_id() ?? ''),
                'class' => 'form-control  alert alert-warning',
            ])
            ->optionsData($optionsDataPostalAddresses)
            ->hint($translator->translate('hint.this.field.is.required'));
            ?>
                <?php
        }
if ($postal_address_count ===  0 && $origin == 'edit') {
    // hide the field but maintain the postaladdress_id that will appear in the $request->bodyParams array
    echo Html::a($translator->translate('client.postaladdress.add'), $urlGenerator->generate('postaladdress/add', ['client_id' => $client->getClient_id(), 'origin' => 'client']), ['class' => 'btn btn-warning btn-lg mt-3']);
}
?>
            <?= Html::closeTag('div'); ?>            
        <?= Html::closeTag('div'); ?>        
                
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php
/**
 * @var App\Invoice\Entity\CustomField $custom_field
 */
foreach ($customFields as $custom_field): ?>
                <?php
if ($custom_field->getLocation() !== 2) {
    continue;
}
    ?>
                <?php $cvH->print_field_for_form($custom_field, $clientCustomForm, $translator, $clientCustomValues, $customValues); ?>
        <?php endforeach; ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::Tag('br'); ?>
<?= Html::openTag('div', ['class' => 'card']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?= $translator->translate('personal.information'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Html::openTag('div', ['class' => 'controls']); ?>
                <?= Field::select($form, 'client_gender')
        ->label($translator->translate('gender'))
        ->addInputAttributes(['class' => 'form-control'])
        ->optionsData($optionsDataGender)
        ->value(Html::encode($form->getClient_gender() ?? 0));
?> 
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
            <?php
echo Field::date($form, 'client_birthdate')
->label($translator->translate('birthdate'))
->addInputAttributes([
    'placeholder' => $translator->translate('birthdate'),
    'class' => 'form-control',
    'id' => 'client_birthdate',
    'role' => 'presentation',
    'autocomplete' => 'off',
])
->value(Html::encode(!is_string($form->getClient_birthdate()) && null !== $form->getClient_birthdate()
                                ? $form->getClient_birthdate()->format('Y-m-d') : ''))
->required(false);
?>    
            <?= Field::number($form, 'client_age')
    ->label($translator->translate('client.age'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('client.age'),
        'value' => Html::encode($form->getClient_age() ?? '18'),
        'class' => 'form-control',
        'id' => 'client_age',
    ])
    ->required(true)
    //->min(16) not necessary Related logic: see ClientForm
    // #[Integer(min: 16)]
    // #[Required]
    // private ?int $client_age = null;
    ->step(1)
    ->hint($translator->translate('client.age.hint'))
?>
            <?= $formFields->clientTextField($form, 'client_avs', 'sumex.ssn', false); ?>
            <?= $formFields->clientTextField($form, 'client_insurednumber', 'sumex.insurednumber', false); ?>
            <?= $formFields->clientTextField($form, 'client_veka', 'sumex.veka', false); ?>
            <?= Html::closeTag('div'); ?>    
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php
        /**
         * @var App\Invoice\Entity\CustomField $custom_field
         */
        foreach ($customFields as $custom_field): ?>
                    <?php
        if ($custom_field->getLocation() !== 3) {
            continue;
        }
            ?>
                    <?php $cvH->print_field_for_form($custom_field, $clientCustomForm, $translator, $clientCustomValues, $customValues); ?>
                <?php endforeach; ?>
            <?= Html::closeTag('div'); ?>    
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'card']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?= $translator->translate('tax.information'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= $formFields->clientTextField($form, 'client_vat_id', 'vat.id', false); ?>
        <?= $formFields->clientTextField($form, 'client_tax_code', 'tax.code', false); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php
        /**
         * @var App\Invoice\Entity\CustomField $custom_field
         */
        foreach ($customFields as $custom_field): ?>
                <?php
        if ($custom_field->getLocation() !== 4) {
            continue;
        }
            ?>
                <?php $cvH->print_field_for_form($custom_field, $clientCustomForm, $translator, $clientCustomValues, $customValues); ?>
            <?php endforeach; ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'form-group']); ?>
            <?php if ($customFields): ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div', ['class' => 'col-xl']); ?>
                    <?= Html::openTag('div', ['class' => 'panel panel-default']); ?>
                        <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
                            <?= $translator->translate('custom.fields'); ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div', ['class' => 'panel-body']); ?>
                            <?php
                            /**
                             * @var App\Invoice\Entity\CustomField $custom_field
                             */
                            foreach ($customFields as $custom_field): ?>
                                <?php
                            if ($custom_field->getLocation() !== 0) {
                                continue;
                            }
                                ?>
                                <?php $cvH->print_field_for_form($custom_field, $clientCustomForm, $translator, $clientCustomValues, $customValues); ?>
                            <?php endforeach; ?>
                        <?= Html::closeTag('div'); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
            <?php endif; ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= $button::backSave(); ?>                
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>



            