<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Client\ClientForm $form
 * @var App\Invoice\ClientCustom\ClientCustomForm $clientCustomForm
 * @var App\Invoice\Entity\Client $client
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

<?= Field::select($form, 'client_title')
    ->label($translator->translate('client.title'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value($form->getClient_title())
    ->prompt($translator->translate('none'))
    ->optionsData([
        $translator->translate('client.title.mr'),
        $translator->translate('client.title.mrs'),
        $translator->translate('client.title.miss'),
        $translator->translate('client.title.doctor'),
        $translator->translate('client.title.professor'),
    ])
    ->required(false);
?>
    
<?= Field::text($form, 'client_name')
    ->label($translator->translate('client.name'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClient_name() ?? ''),
        'placeholder' => $translator->translate('client.name'),
        'class' => 'form-control',
    ])
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
<?= Field::text($form, 'client_surname')
    ->label($translator->translate('client.surname'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClient_surname() ?? ''),
        'placeholder' => $translator->translate('client.surname'),
        'class' => 'form-control',
    ])
    ->required(false);
?>


<?= Field::email($form, 'client_email')
    ->label($translator->translate('email'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('email'),
        'value' =>  Html::encode($form->getClient_email() ?? ''),
        'class' => 'form-control',
        'id' => 'client_email',
    ])
    ->required(false);
?>

<?= Field::telephone($form, 'client_mobile')
    ->label($translator->translate('mobile'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('mobile'),
        'value' => Html::encode($form->getClient_mobile() ?? ''),
        'class' => 'form-control',
        'id' => 'client_mobile',
    ])
    ->required(false);
?>

<?= Field::text($form, 'client_group')
    ->label($translator->translate('client.group'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClient_group() ?? ''),
        'placeholder' => $translator->translate('client.group'),
        'class' => 'form-control',
    ])
    ->required(false);
?>

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

<?= Field::text($form, 'client_number')
    ->label($translator->translate('client.number'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClient_number() ?? ''),
        'placeholder' => $translator->translate('client.number'),
        'class' => 'form-control',
    ])
    ->required(false);
?>  
    
<?php
    $options = [];
/** @var string $language */
foreach ($s->locale_language_array() as $language) {
    $options[$language] = ucfirst($language);
}
echo Field::select($form, 'client_language')
    ->label($translator->translate('language'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'client_language',
    ])
->value(strlen($form->getClient_language() ?? '') > 0 ? $form->getClient_language() : $selectedLanguage)
->optionsData($options)
->required(true)
->hint($translator->translate('hint.this.field.is.required'));
?>  

<?= Html::Tag('br'); ?>
<?= Html::openTag('div', ['class' => 'card']); ?>
    <?= Html::openTag('div', ['class' => 'card-header']); ?>
        <?= $translator->translate('address'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_address_1')
                ->label($translator->translate('street.address'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('street.address'),
                    'value' => Html::encode($form->getClient_address_1() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_address_1',
                ])
                ->required(true)
                ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::tag('br'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_address_2')
    ->label($translator->translate('street.address.2'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('street.address.2'),
        'value' => Html::encode($form->getClient_address_2() ?? ''),
        'class' => 'form-control',
        'id' => 'client_address_2',
    ])
    ->required(false);
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_building_number')
    ->label($translator->translate('client.building.number'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('client.building.number'),
        'value' => Html::encode($form->getClient_building_number() ?? ''),
        'class' => 'form-control',
        'id' => 'client_building_number',
    ])
    ->required(false);
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_city')
    ->label($translator->translate('city'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('city'),
        'value' => Html::encode($form->getClient_city() ?? ''),
        'class' => 'form-control',
        'id' => 'client_city',
    ])
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_state')
    ->label($translator->translate('state'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('state'),
        'value' => Html::encode($form->getClient_state() ?? ''),
        'class' => 'form-control',
        'id' => 'client_state',
    ])
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_zip')
    ->label($translator->translate('zip'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('zip'),
        'value' => Html::encode($form->getClient_zip() ?? ''),
        'class' => 'form-control',
        'id' => 'client_zip',
    ])
    ->required(false);
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php
    /** @var string $country */
    foreach ($countries as $cldr => $country) {
        $options[$country] = ucfirst($country);
    }
echo Field::select($form, 'client_country')
->label($translator->translate('country'))
->addInputAttributes([
    'id' => 'client_country',
    'class' => 'form-control',
    'selected' => $s->check_select(($form->getClient_country() ?? $client->getClient_country()), $selectedCountry),
])
->optionsData($options)
->required(true)
->hint($translator->translate('hint.this.field.is.required'));
?>
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
            <?= Field::telephone($form, 'client_phone')
        ->label($translator->translate('phone'))
        ->addInputAttributes([
            'placeholder' => $translator->translate('phone'),
            'value' => Html::encode($form->getClient_phone() ?? ''),
            'class' => 'form-control',
            'id' => 'client_phone',
        ])
        ->required(false);
?>
            <?= Field::telephone($form, 'client_fax')
    ->label($translator->translate('fax'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('phone'),
        'value' => Html::encode($form->getClient_fax() ?? ''),
        'class' => 'form-control',
        'id' => 'client_fax',
    ])
    ->required(false);
?>            
            <?= Field::url($form, 'client_web')
    ->label($translator->translate('web'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('web'),
        'value' => Html::encode($form->getClient_web() ?? ''),
        'class' => 'form-control',
        'id' => 'client_web',
    ])
    ->required(false);
?>
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
->value(Html::encode(!is_string($form->getClient_birthdate()) && null !== $form->getClient_birthdate() ?
                                $form->getClient_birthdate()->format('Y-m-d') : ''))
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
            <?= Field::text($form, 'client_avs')
    ->label($translator->translate('sumex.ssn'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('sumx.ssn'),
        'value' =>  Html::encode($form->getClient_avs() ?? ''),
        'class' => 'form-control',
        'id' => 'client_avs',
    ])
    ->required(false);
?>
            <?= Field::text($form, 'client_insurednumber')
    ->label($translator->translate('sumex.insurednumber'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('sumex.insurednumber'),
        'value' =>  Html::encode($form->getClient_insurednumber() ?? ''),
        'class' => 'form-control',
        'id' => 'client_insurednumber',
    ])
    ->required(false);
?>
            <?= Field::text($form, 'client_veka')
    ->label($translator->translate('sumex.veka'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('sumex.veka'),
        'value' =>  Html::encode($form->getClient_veka() ?? ''),
        'class' => 'form-control',
        'id' => 'client_veka',
    ])
    ->required(false);
?>
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
        <?= Field::text($form, 'client_vat_id')
            ->label($translator->translate('vat.id'))
            ->addInputAttributes([
                'placeholder' => $translator->translate('vat.id'),
                'value' =>  Html::encode($form->getClient_vat_id() ?? ''),
                'class' => 'form-control',
                'id' => 'client_vat_id',
            ])
            ->required(false);
?>
        <?= Field::text($form, 'client_tax_code')
    ->label($translator->translate('tax.code'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('tax.code'),
        'value' =>  Html::encode($form->getClient_tax_code() ?? ''),
        'class' => 'form-control',
        'id' => 'client_tax_code',
    ])
    ->required(false);
?>
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



            