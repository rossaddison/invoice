<?php
declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Arrays\ArrayHelper;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>
<?= Html::openTag('h1');?>
    <?= Html::encode($title); ?>
<?=Html::closeTag('h1'); ?>
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
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ClientForm')
    ->open()
?>
<?= Field::buttonGroup()
    ->addContainerClass('btn-group btn-toolbar float-end')
    ->buttonsData([
        [
            $translator->translate('invoice.cancel'),
            'type' => 'reset',
            'class' => 'btn btn-sm btn-danger',
            'name'=> 'btn_cancel'
        ],
        [
            $translator->translate('invoice.submit'),
            'type' => 'submit',
            'class' => 'btn btn-sm btn-primary',
            'name' => 'btn_send'
        ],
]) ?>
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
    foreach (ArrayHelper::map($s->expandDirectoriesMatrix($aliases->get('@language'), 0), 'name', 'name') as $language) { 
        $options[$language] = ucfirst($language);                    
    }
    echo Field::select($form, 'client_language')
    ->label($translator->translate('i.language'),['control-label'])
    ->addInputAttributes([
        'value' => '0',
        'class' => 'form-control',
        'id' => 'client_language'
    ])    
    ->addInputAttributes(['selected' => $s->check_select(Html::encode($form->getClient_language() ?? ''), $language)])
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
                ->label($translator->translate('i.street_address'), ['form-label'])
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
                ->label($translator->translate('i.street_address_2'), ['form-label'])
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
                ->label($translator->translate('invoice.client.building.number'), ['form-label'])
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
                ->label($translator->translate('i.city'), ['form-label'])
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
                ->label($translator->translate('i.state'), ['form-label'])
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
                ->label($translator->translate('i.zip'), ['form-label'])
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
                foreach ($countries as $cldr => $country) { 
                    $options[$country] = ucfirst($country);                    
                }
                echo Field::select($form, 'client_country')
                ->label($translator->translate('i.country'),['control-label'])
                ->addInputAttributes([
                    'id' => 'client_country', 
                    'class' => 'form-control',
                    'selected' => $s->check_select(($form->getClient_country() ?? $client->getClient_country()), $country)
                ])    
                ->optionsData($options)
                ->required(true)
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?php foreach ($custom_fields as $custom_field): ?>
                <?php
                if ($custom_field->getLocation() !== 1) {
                    continue;
                }
                ?>
                <?=
                $cvH->print_field_for_form($client_custom_values,
                        $custom_field,
                        // Custom values to fill drop down list if a dropdown box has been created
                        $custom_values,
                        // Class for div surrounding input
                        'col-xs-12 col-sm-6',
                        // Class surrounding above div
                        'form-group',
                        // Label class similar to above
                        'control-label');
                ?>
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
                ->label($translator->translate('i.phone'), ['form-label'])
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
                ->label($translator->translate('i.fax'), ['form-label'])
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
                ->label($translator->translate('i.mobile'), ['class' => 'form-label'])
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
                ->label($translator->translate('i.email'), ['class' => 'form-label'])
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
                ->label($translator->translate('i.web'), ['class' => 'form-label'])
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
                <?php if ($postal_address_count > 0) { ?>
                    <?= Field::select($form, 'postaladdress_id')
                        ->label($translator->translate('invoice.client.postaladdress.available'))    
                        ->required(true)
                        ->addInputAttributes([
                            'value' => Html::encode($form->getPostaladdress_id() ?? ''),
                            'class' => 'form-control  alert alert-warning'
                        ])
                        ->optionsData($postaladdresses)
                        ->hint($translator->translate('invoice.hint.this.field.is.required'));    
                    ?>
                <?php
                } else {
                    // hide the field but maintain the postaladdress_id that will appear in the $request->bodyParams array
                    echo Html::a($translator->translate('invoice.client.postaladdress.add'), $urlGenerator->generate('postaladdress/add', ['client_id' => $client->getClient_id()]), ['class' => 'btn btn-warning btn-lg mt-3']);
                }
                ?>
            <?= Html::closeTag('div'); ?>            
        <?= Html::closeTag('div'); ?>        
                
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php foreach ($custom_fields as $custom_field): ?>
                <?php
                if ($custom_field->getLocation() !== 2) {
                    continue;
                }
                ?>
                <?=
                $cvH->print_field_for_form($client_custom_values,
                        $custom_field,
                        // Custom values to fill drop down list if a dropdown box has been created
                        $custom_values,
                        // Class for div surrounding input
                        'col-xs-12 col-sm-6',
                        // Class surrounding above div
                        'form-group',
                        // Label class similar to above
                        'control-label');
                ?>
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
                    ->label($translator->translate('i.gender'),['class' => 'form-label'])    
                    ->addInputAttributes(['class' => 'form-control'])
                    ->optionsData($optionsDataGender)
                    ->value(Html::encode($form->getClient_gender() ?? 0 ));
                ?> 
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group has-feedback']); ?>
            <?php
                $bdate = $datehelper->get_or_set_with_style($form->getClient_birthdate() ?? new \DateTimeImmutable('now'));
                echo Field::text($form, 'client_birthdate')
                ->label($translator->translate('i.birthdate'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.birthdate'),
                    'class' => 'form-control input-sm datepicker',
                    'id' => 'client_birthdate',
                    'role' => 'presentation',
                    'autocomplete' => 'off'
                ])
                ->value($bdate)        
                ->required(false)
                ->readonly(true)        
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>    
            <?= Field::number($form, 'client_age')
                ->label($translator->translate('invoice.client.age'), ['form-label'])
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
                ->label($translator->translate('i.sumex_ssn'), ['class' => 'form-label'])
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
                ->label($translator->translate('i.sumex_insurednumber'), ['class' => 'form-label'])
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
                ->label($translator->translate('i.sumex_veka'), ['class' => 'form-label'])
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
                <?php foreach ($custom_fields as $custom_field): ?>
                    <?php
                    if ($custom_field->getLocation() !== 3) {
                        continue;
                    }
                    ?>
                    <?=
                    $cvH->print_field_for_form(
                        $client_custom_values,
                        $custom_field,
                        // Custom values to fill drop down list if a dropdown box has been created
                        $custom_values,
                        // Class for div surrounding input
                        'col-xs-12 col-sm-6',
                        // Class surrounding above div
                        'form-group',
                        // Label class similar to above
                        'control-label');
                    ?>
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
            ->label($translator->translate('i.vat_id'), ['class' => 'form-label'])
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
            ->label($translator->translate('i.tax_code'), ['class' => 'form-label'])
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
            <?php foreach ($custom_fields as $custom_field): ?>
                <?php
                if ($custom_field->getLocation() !== 4) {
                    continue;
                }
                ?>
                <?=
                $cvH->print_field_for_form($client_custom_values,
                    $custom_field,
                    // Custom values to fill drop down list if a dropdown box has been created
                    $custom_values,
                    // Class for div surrounding input
                    'col-xs-12 col-sm-6',
                    // Class surrounding above div
                    'form-group',
                    // Label class similar to above
                    'control-label');
                ?>
            <?php endforeach; ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'form-group']); ?>
            <?php if ($custom_fields): ?>
            <?= Html::openTag('div',['class' => 'row']); ?>
                <?= Html::openTag('div',['class' => 'col-xs-12 col-md-6']); ?>
                    <?= Html::openTag('div',['class' => 'panel panel-default']); ?>
                        <?= Html::openTag('div',['class' => 'panel-heading']); ?>
                            <?= $translator->translate('i.custom_fields'); ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div', ['class' => 'panel-body']); ?>
                            <?php foreach ($custom_fields as $custom_field): ?>
                                <?php
                                if ($custom_field->getLocation() !== 0) {
                                    continue;
                                }
                                ?>
                                <?=
                                $cvH->print_field_for_form($client_custom_values,
                                    $custom_field,
                                    // Custom values to fill drop down list if a dropdown box has been created
                                    $custom_values,
                                    // Class for div surrounding input
                                    'col-xs-12 col-sm-6',
                                    // Class surrounding above div
                                    'form-group',
                                    // Label class similar to above
                                    'control-label');
                                ?>
                            <?php endforeach; ?>
                        <?= Html::closeTag('div'); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
            <?php endif; ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>



            