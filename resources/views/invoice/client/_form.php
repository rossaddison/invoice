<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Arrays\ArrayHelper;

/**
 * @var App\Invoice\Client\ClientForm $form
 * @var App\Invoice\ClientCustom\ClientCustomForm $clientCustomForm
 * @var App\Invoice\Entity\Client $client
 * 
 * @see config\common\params.php 'cvH'
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
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= $translator->translate('i.client_form'); ?>
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
    ->header($translator->translate('invoice.client.error.summary'))
    ->onlyProperties(...['client_name', 'client_surname', 'client_email', 'client_age'])    
    ->onlyCommonErrors()
?>
<?= 
    Html::openTag('div', ['class'=> 'card']),
        Html::openTag('div', ['class'=>'card-header d-flex justify-content-between']), 
            $translator->translate('i.personal_information'),
            Html::openTag('div', ['class' => 'p-2']), 
                Field::checkbox($form, 'client_active')
                ->inputLabelAttributes(['class' => 'form-check-label'])    
                ->enclosedByLabel(true)
                ->inputClass('form-check-input')
                ->ariaDescribedBy($translator->translate('i.client_active')),
            Html::closeTag('div'),
        Html::closeTag('div'),
    Html::closeTag('div')    
?>

<?= Field::select($form, 'client_title')
    ->label($translator->translate('invoice.client.title'))
    ->addInputAttributes([
        'class' => 'form-control'
    ])
    ->value($form->getClient_title())
    ->prompt($translator->translate('i.none'))
    ->optionsData([
        $translator->translate('invoice.client.title.mr'),
        $translator->translate('invoice.client.title.mrs'),
        $translator->translate('invoice.client.title.miss'),
        $translator->translate('invoice.client.title.doctor'),
        $translator->translate('invoice.client.title.professor'),
    ])                
    ->required(false)    
    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
?>
    
<?= Field::text($form, 'client_name')
    ->label($translator->translate('i.client_name'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClient_name() ?? ''),
        'placeholder' => $translator->translate('i.client_name'),
        'class' => 'form-control'
    ])
    ->required(true)    
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>
<?= Field::text($form, 'client_surname')
    ->label($translator->translate('i.client_surname'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClient_surname() ?? ''), 
        'placeholder' => $translator->translate('i.client_surname'),
        'class' => 'form-control'
    ])
    ->required(true)        
    ->hint($translator->translate('invoice.hint.this.field.is.required'));
?>

<?= Field::text($form, 'client_group')
    ->label($translator->translate('invoice.client.group'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClient_group() ?? ''), 
        'placeholder' => $translator->translate('invoice.client.group'),
        'class' => 'form-control'
    ])
    ->required(false)        
    ->hint($translator->translate('invoice.hint.this.field.is.not.required'));
?>

<?= Field::select($form, 'client_frequency')
    ->label($translator->translate('invoice.client.frequency'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClient_frequency() ?? ''), 
        'placeholder' => $translator->translate('invoice.client.frequency'),
        'class' => 'form-control'
    ])
    ->optionsData($optionsDataClientFrequencyDropdownFilter)            
    ->required(false)        
    ->hint($translator->translate('invoice.hint.this.field.is.not.required'));
?>

<?= Field::text($form, 'client_number')
    ->label($translator->translate('invoice.client.number'))
    ->addInputAttributes([
        'value' => Html::encode($form->getClient_number() ?? ''), 
        'placeholder' => $translator->translate('i.client_number'),
        'class' => 'form-control'  
    ])
    ->required(false)    
    ->hint($translator->translate('invoice.hint.this.field.is.not.required'));
?>  
    
<?php
    $options = [];
    /** @var string $language */
    foreach (ArrayHelper::map($s->expandDirectoriesMatrix($aliases->get('@language'), 0), 'name', 'name') as $language) { 
        $options[$language] = ucfirst($language);                    
    }
    echo Field::select($form, 'client_language')
    ->label($translator->translate('i.language'))
    ->addInputAttributes([
        'value' => '0',
        'class' => 'form-control',
        'id' => 'client_language'
    ])    
    ->addInputAttributes(['selected' => $s->check_select(Html::encode($form->getClient_language() ?? ''), $selectedLanguage)])
    ->optionsData($options)        
    ->required(true)    
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>  

<?= Html::Tag('br'); ?>
<?= Html::openTag('div',['class' => 'card']); ?>
    <?= Html::openTag('div',['class' => 'card-header']); ?>
        <?= $translator->translate('i.address'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_address_1')
                ->label($translator->translate('i.street_address'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.street_address'),
                    'value' => Html::encode($form->getClient_address_1() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_address_1',
                ])
                ->required(true)    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::tag('br'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_address_2')
                ->label($translator->translate('i.street_address_2'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.street_address_2'),
                    'value' => Html::encode($form->getClient_address_2() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_address_2'
                ])
                ->required(true)    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_building_number')
                ->label($translator->translate('invoice.client.building.number'))
                ->addInputAttributes([ 
                    'placeholder' => $translator->translate('invoice.client.building.number'),
                    'value' => Html::encode($form->getClient_building_number() ?? ''),
                    'class' => 'form-control',                
                    'id' => 'client_building_number',
                ])
                ->required(false)    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_city')
                ->label($translator->translate('i.city'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.city'),                
                    'value' => Html::encode($form->getClient_city() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_city', 
                ])
                ->required(true)            
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_state')
                ->label($translator->translate('i.state'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.state'),
                    'value' => Html::encode($form->getClient_state() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_state',    
                ])
                ->required(true)    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_zip')
                ->label($translator->translate('i.zip'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.zip'),
                    'value' => Html::encode($form->getClient_zip() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_zip', 
                ])
                ->required(true)
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?php
                /** @var string $country */
                foreach ($countries as $cldr => $country) { 
                    $options[$country] = ucfirst($country);                    
                }
                echo Field::select($form, 'client_country')
                ->label($translator->translate('i.country'))
                ->addInputAttributes([
                    'id' => 'client_country', 
                    'class' => 'form-control',
                    'selected' => $s->check_select(($form->getClient_country() ?? $client->getClient_country()), $selectedCountry)
                ])    
                ->optionsData($options)
                ->required(true)
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
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
<?= Html::openTag('div',['class' => 'card']); ?>
    <?= Html::openTag('div',['class' => 'card-header']); ?>
        <?= $translator->translate('i.contact_information'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::telephone($form, 'client_phone')
                ->label($translator->translate('i.phone'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.phone'),
                    'value' => Html::encode($form->getClient_phone() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_phone'
                ])
                ->required(false)
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
            <?= Field::telephone($form, 'client_fax')
                ->label($translator->translate('i.fax'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.phone'),
                    'value' => Html::encode($form->getClient_fax() ?? ''),
                    'class' => 'form-control', 
                    'id' => 'client_fax', 
                ])
                ->required(false)    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
            <?= Field::telephone($form, 'client_mobile')
                ->label($translator->translate('i.mobile'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.mobile'),
                    'value' => Html::encode($form->getClient_mobile() ?? ''),
                    'class' => 'form-control', 
                    'id' => 'client_mobile'
                ])
                ->required(true)    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Field::email($form, 'client_email')
                ->label($translator->translate('i.email'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.email'),
                    'value' =>  Html::encode($form->getClient_email() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_email'
                ])
                ->required(true)    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Field::text($form, 'client_web')
                ->label($translator->translate('i.web'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.web'),
                    'value' => Html::encode($form->getClient_web() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_web',
                ])
                ->required(false)
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
           <?= Html::openTag('div'); ?>
                <label for="postaladdress_id"><?= $translator->translate('invoice.client.postaladdress.available'); ?>: </label>
            <?= Html::closeTag('div'); ?>           
            <?= Html::openTag('div',['class' => 'input-group']); ?>
                <?php
                    // only allow the postal address add button if we are editing the client
                    // we add a client on the dashboard
                    if ($postal_address_count > 0 && $origin == 'edit') { ?>
                    <?= Field::select($form, 'postaladdress_id')
                        ->label($translator->translate('invoice.client.postaladdress.available'))    
                        ->required(true)
                        ->addInputAttributes([
                            'value' => Html::encode($form->getPostaladdress_id() ?? ''),
                            'class' => 'form-control  alert alert-warning'
                        ])
                        ->optionsData($optionsDataPostalAddresses)
                        ->hint($translator->translate('invoice.hint.this.field.is.required'));    
                    ?>
                <?php
                }
                if ($postal_address_count ===  0 && $origin == 'edit')
                {
                    // hide the field but maintain the postaladdress_id that will appear in the $request->bodyParams array
                    echo Html::a($translator->translate('invoice.client.postaladdress.add'), $urlGenerator->generate('postaladdress/add', ['client_id' => $client->getClient_id(), 'origin' => 'client']), ['class' => 'btn btn-warning btn-lg mt-3']);
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
<?= Html::openTag('div',['class' => 'card']); ?>
    <?= Html::openTag('div',['class' => 'card-header']); ?>
        <?= $translator->translate('i.personal_information'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Html::openTag('div',['class' => 'controls']); ?>
                <?= Field::select($form, 'client_gender')
                    ->label($translator->translate('i.gender'))    
                    ->addInputAttributes(['class' => 'form-control'])
                    ->optionsData($optionsDataGender)
                    ->value(Html::encode($form->getClient_gender() ?? 0 ));
                ?> 
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group has-feedback']); ?>
            <?php
                echo Field::text($form, 'client_birthdate')
                ->label($translator->translate('i.birthdate'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.birthdate'),
                    'class' => 'form-control input-sm datepicker',
                    'id' => 'client_birthdate',
                    'role' => 'presentation',
                    'autocomplete' => 'off'
                ])
                ->value(Html::encode(null!== ($clientBirthDate = $form->getClient_birthdate()) && !is_string($clientBirthDate) ? 
                                $clientBirthDate->format($dateHelper->style()) : ''))        
                ->required(false)
                ->readonly(true)        
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>    
            <?= Field::number($form, 'client_age')
                ->label($translator->translate('invoice.client.age'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('invoice.client.age'),
                    'value' => Html::encode($form->getClient_age() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_age'
                ])
                ->required(true)
                //->min(16) not necessary @see ClientForm
                // #[Integer(min: 16)]
                // #[Required]
                // private ?int $client_age = null;
                ->step(1)
                ->hint($translator->translate('invoice.client.age.hint'))
            ?>
            <?= Field::text($form, 'client_avs')
                ->label($translator->translate('i.sumex_ssn'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.sumx_ssn'),
                    'value' =>  Html::encode($form->getClient_avs() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_avs'
                ])
                ->required(true)    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Field::text($form, 'client_insurednumber')
                ->label($translator->translate('i.sumex_insurednumber'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.sumex_insurednumber'),
                    'value' =>  Html::encode($form->getClient_insurednumber() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_insurednumber'
                ])
                ->required(false)    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
            <?= Field::text($form, 'client_veka')
                ->label($translator->translate('i.sumex_veka'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.sumex_veka'),
                    'value' =>  Html::encode($form->getClient_veka() ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_veka'
                ])
                ->required(false)    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
            <?= Html::closeTag('div'); ?>    
            <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
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

<?= Html::openTag('div',['class' => 'card']); ?>
    <?= Html::openTag('div',['class' => 'card-header']); ?>
        <?= $translator->translate('i.tax_information'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div',['class' => 'row']); ?>
        <?= Field::text($form, 'client_vat_id')
            ->label($translator->translate('i.vat_id'))
            ->addInputAttributes([
                'placeholder' => $translator->translate('i.vat_id'),
                'value' =>  Html::encode($form->getClient_vat_id() ?? ''),
                'class' => 'form-control',
                'id' => 'client_vat_id'
            ])
            ->required(false)    
            ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
        ?>
        <?= Field::text($form, 'client_tax_code')
            ->label($translator->translate('i.tax_code'))
            ->addInputAttributes([
                'placeholder' => $translator->translate('i.tax_code'),
                'value' =>  Html::encode($form->getClient_tax_code() ?? ''),
                'class' => 'form-control',
                'id' => 'client_tax_code'
            ])
            ->required(true)    
            ->hint($translator->translate('invoice.hint.this.field.is.required')); 
        ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
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
        <?= Html::openTag('div',['class' => 'form-group']); ?>
            <?php if ($customFields): ?>
            <?= Html::openTag('div',['class' => 'row']); ?>
                <?= Html::openTag('div',['class' => 'col-xl']); ?>
                    <?= Html::openTag('div',['class' => 'panel panel-default']); ?>
                        <?= Html::openTag('div',['class' => 'panel-heading']); ?>
                            <?= $translator->translate('i.custom_fields'); ?>
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
<?= $button::back_save(); ?>                
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>



            