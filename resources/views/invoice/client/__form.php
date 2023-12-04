<?php
declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Arrays\ArrayHelper;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>
<h1><?= Html::encode($title) ?></h1>
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
<?= $s->trans('clients_form'); ?>
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
            $s->trans('personal_information'),
            Html::openTag('div', ['class' => 'p-2']), 
                Field::checkbox($form, 'client_active')
                ->inputValue(!$body['client_active']),
            Html::closeTag('div'),
        Html::closeTag('div'),
    Html::closeTag('div')    
?>
    
<?= Field::text($form, 'client_name')
    ->label($s->trans('client_name'))
    ->addInputAttributes([
        'value' => Html::encode($body['client_name'] ?? ''),
        'placeholder' => $s->trans('client_name'),
        'class' => 'form-control'
    ])
    ->required(true)    
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>
<?= Field::text($form, 'client_surname')
    ->label($s->trans('client_surname'))
    ->addInputAttributes([
        'value' => Html::encode($body['client_surname'] ?? ''), 
        'placeholder' => $s->trans('client_surname'),
        'class' => 'form-control'
    ])
    ->required(true)        
    ->hint($translator->translate('invoice.hint.this.field.is.required'));
?>
<?= Field::text($form, 'client_number')
    ->label($translator->translate('invoice.client.number'))
    ->addInputAttributes([
        'value' => Html::encode($body['client_number'] ?? ''), 
        'placeholder' => $s->trans('client_number'),
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
    ->label($s->trans('language'),['control-label'])
    ->addInputAttributes([
        'value' => '0',
        'class' => 'form-control',
        'id' => 'client_language'
    ])    
    ->addInputAttributes(['selected' => $s->check_select(Html::encode($body['client_language'] ?? ''), $language)])
    ->optionsData($options)        
    ->required(true)    
    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
?>  

<br>
<?= Html::openTag('div',['class' => 'card']); ?>
    <?= Html::openTag('div',['class' => 'card-header']); ?>
        <?= $s->trans('address'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_address_1')
                ->label($s->trans('street_address'), ['form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('street_address'),
                    'value' => Html::encode($body['client_address_1'] ?? ''),
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
                ->label($s->trans('street_address_2'), ['form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('street_address_2'),
                    'value' => Html::encode($body['client_address_2'] ?? ''),
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
                    'value' => Html::encode($body['client_building_number'] ?? ''),
                    'class' => 'form-control',                
                    'id' => 'client_building_number',
                ])
                ->required(false)    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_city')
                ->label($s->trans('city'), ['form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('city'),                
                    'value' => Html::encode($body['client_city'] ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_city', 
                ])
                ->required(true)            
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_state')
                ->label($s->trans('state'), ['form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('state'),
                    'value' => Html::encode($body['client_state'] ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_state',    
                ])
                ->required(true)    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'client_zip')
                ->label($s->trans('zip'), ['form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('zip'),
                    'value' => Html::encode($body['client_zip'] ?? ''),
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
                ->label($s->trans('country'),['control-label'])
                ->addInputAttributes([
                    'id' => 'client_country', 
                    'class' => 'form-control',
                    'selected' => $s->check_select(($body['client_country'] ?? $client->getClient_country()), $country)
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
<br>
<?= Html::openTag('div',['class' => 'card']); ?>
    <?= Html::openTag('div',['class' => 'card-header']); ?>
        <?= $s->trans('contact_information'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::telephone($form, 'client_phone')
                ->label($s->trans('phone'), ['form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('phone'),
                    'value' => Html::encode($body['client_phone'] ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_phone'
                ])
                ->required(false)
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
            <?= Field::telephone($form, 'client_fax')
                ->label($s->trans('fax'), ['form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('phone'),
                    'value' => Html::encode($body['client_fax'] ?? ''),
                    'class' => 'form-control', 
                    'id' => 'client_fax', 
                ])
                ->required(false)    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
            <?= Field::telephone($form, 'client_mobile')
                ->label($s->trans('fax'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('mobile'),
                    'value' => Html::encode($body['client_mobile'] ?? ''),
                    'class' => 'form-control', 
                    'id' => 'client_mobile'
                ])
                ->required(true)    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Field::email($form, 'client_email')
                ->label($s->trans('email'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('email'),
                    'value' =>  Html::encode($body['client_email'] ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_email'
                ])
                ->required(true)    
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
            <?= Field::text($form, 'client_web')
                ->label($s->trans('web'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('web'),
                    'value' => Html::encode($body['client_web'] ?? ''),
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
                    <select name="postaladdress_id" id="postaladdress_id"
                            class="form-control">
                                <?php foreach ($postaladdresses as $postaladdress) { ?>
                            <option value="<?php echo $postaladdress->getId(); ?>"
                            <?php echo $s->check_select(Html::encode($body['client_postaladdress_id'] ?? $postaladdress->getId()), $postaladdress->getId()); ?>>
                            <?php echo $postaladdress->getStreet_name() . ', ' . $postaladdress->getAdditional_street_name() . ', ' . $postaladdress->getBuilding_number() . ', ' . $postaladdress->getCity_name(); ?>
                            </option>
                    <?php } ?>
                    </select>
                <?php
                } else {
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
<br>
<?= Html::openTag('div',['class' => 'card']); ?>
    <?= Html::openTag('div',['class' => 'card-header']); ?>
        <?= $s->trans('personal_information'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <label for="client_gender"  class="form-label"><?= $s->trans('gender'); ?></label>
            <?= Html::openTag('div',['class' => 'controls']); ?>
                <select name="client_gender" id="client_gender"
                        class="form-control" data-minimum-results-for-search="Infinity">
                            <?php
                            $genders = [
                                $s->trans('gender_male'),
                                $s->trans('gender_female'),
                                $s->trans('gender_other'),
                            ];
                            foreach ($genders as $key => $val) {
                                ?>
                        <option value=" <?php echo $key; ?>" <?php $s->check_select(Html::encode($body['client_gender'] ?? 0 ), $key) ?>>
                <?php echo $val; ?>
                        </option>
            <?php } ?>
                </select>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group has-feedback']); ?>
            <?php
                $bdate = $datehelper->get_or_set_with_style($body['client_birthdate']);
                echo Field::text($form, 'client_birthdate')
                ->label($s->trans('birthdate'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('birthdate'),
                    'value' => null !== $bdate ? Html::encode($bdate instanceof \DateTimeImmutable ? $bdate->format($datehelper->style()) : $bdate) : null,
                    'class' => 'form-control input-sm datepicker',
                    'id' => 'client_birthdate',
                    'role' => 'presentation',
                    'autocomplete' => 'off'
                ])
                ->required(false)
                ->readonly(true)        
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>    
            <?= Field::number($form, 'client_age')
                ->label($translator->translate('invoice.client.age'), ['form-label'])
                ->addInputAttributes([
                    'placeholder' => $translator->translate('invoice.client.age'),
                    'value' => Html::encode($body['client_age'] ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_age'
                ])
                ->required(true)
                ->min(16)
                ->step(1)
                ->hint($translator->translate('invoice.client.age.hint'))
            ?>
            <?= Field::text($form, 'client_avs')
                ->label($s->trans('sumex_ssn'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('sumx_ssn'),
                    'value' =>  Html::encode($body['client_avs'] ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_avs'
                ])
                ->required(false)    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
            <?= Field::text($form, 'client_insurednumber')
                ->label($s->trans('sumex_insuredumber'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('sumex_insurednumber'),
                    'value' =>  Html::encode($body['client_insurednumber'] ?? ''),
                    'class' => 'form-control',
                    'id' => 'client_insurednumber'
                ])
                ->required(false)    
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
            <?= Field::text($form, 'client_veka')
                ->label($s->trans('sumex_veka'), ['class' => 'form-label'])
                ->addInputAttributes([
                    'placeholder' => $s->trans('sumex_veka'),
                    'value' =>  Html::encode($body['client_veka'] ?? ''),
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
        <?= $s->trans('tax_information'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div',['class' => 'row']); ?>
        <?= Field::text($form, 'client_vat_id')
            ->label($s->trans('vat_id'), ['class' => 'form-label'])
            ->addInputAttributes([
                'placeholder' => $s->trans('vat_id'),
                'value' =>  Html::encode($body['client_vat_id'] ?? ''),
                'class' => 'form-control',
                'id' => 'client_vat_id'
            ])
            ->required(false)    
            ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
        ?>
        <?= Field::text($form, 'client_tax_code')
            ->label($s->trans('tax_code'), ['class' => 'form-label'])
            ->addInputAttributes([
                'placeholder' => $s->trans('tax_code'),
                'value' =>  Html::encode($body['client_tax_code'] ?? ''),
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
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <?= $s->trans('custom_fields'); ?>
                        </div>
                        <div class="panel-body">
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
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?php            
    echo "This is the dump: " .\Yiisoft\VarDumper\VarDumper::dump($body['client_active']);
?>