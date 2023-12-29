<?php
declare(strict_types=1);

use Yiisoft\Form\Field\Base\InputData\PureInputData;
use Yiisoft\Form\Field\Text;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Arrays\ArrayHelper;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
$errors = $form->getValidationResult()?->getErrors();
if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}
?>
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>

<?=
        Form::tag()
        ->post($urlGenerator->generate(...$action))
        ->enctypeMultipartFormData()
        ->csrf($csrf)
        ->id('ClientForm')
        ->open()
?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.clients_form'); ?></h1>
    <?php
    echo $buttons;
    ?>
    <div class="mb-3 form-group btn-group-sm">
    </div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <?= $translator->translate('i.personal_information'); ?>
        <div  class="p-2">
            <label for="client_active" class="control-label ">
                <?= $translator->translate('i.active_client'); ?>
                <input id="client_active" name="client_active" type="checkbox" value="1"
                       <?php $s->check_select(Html::encode($body['client_active'] ?? ''), 1, '==', true) ?>>
            </label>
        </div>
    </div>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <div class="mb-3 form-group">
            <?php 
            $InputDataName = new PureInputData(
                name: 'client_name',
                value: Html::encode($body['client_name'] ?? ''),
                label: $translator->translate('i.client_name'),
                hint: 'This field is required',
                id: 'client_name',
                placeholder: $translator->translate('i.client_name')
            );
            echo Html::openTag('div',['class'=>'input-group']);
            echo Text::widget()
            ->inputData($InputDataName)
            ->addInputClass('form-control')        
            ->render();
            echo Html::closeTag('div');
        ?>
        </div>
        
        <div class="mb-3 form-group">
            <label for="client_surname" class="form-label"><?= $translator->translate('i.client_surname'); ?></label>
            <input type="text" class="form-control" name="client_surname" id="client_surname" placeholder="<?= $translator->translate('i.client_surname'); ?>" value="<?= Html::encode($body['client_surname'] ?? '') ?>">
        </div>
        <div class="mb-3 form-group">
            <label for="client_number" class="form-label"><?= $translator->translate('invoice.client.number'); ?></label>
            <input type="text" class="form-control" name="client_number" id="client_number" placeholder="<?= $translator->translate('invoice.client.number'); ?>" value="<?= Html::encode($body['client_number'] ?? '') ?>">
        </div>
        <div class="mb-3 form-group no-margin">
            <label for="client_language" class="form-label">
                <?php echo $translator->translate('i.language'); ?>
            </label>
            <select name="client_language" id="client_language" class="form-control" required>
                <option><?php Html::encode($body['client_language'] ?? ''); ?></option>
                <?php foreach (ArrayHelper::map($s->expandDirectoriesMatrix($aliases->get('@language'), 0), 'name', 'name') as $language) { ?>
                    <option value="<?= $language; ?>"
                            <?php $s->check_select(Html::encode($body['client_language'] ?? ''), $language) ?>>
                            <?= ucfirst($language); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    </div>

</div>
<br>
<div class="card">
    <div class="card-header">
        <?= $translator->translate('i.address'); ?>
    </div>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <div class="mb-3 form-group">
            <label for="client_address_1" class="form-label"><?= $translator->translate('i.street_address'); ?></label>
            <input type="text" class="form-control" name="client_address_1" id="client_address_1" placeholder="<?= $translator->translate('i.street_address'); ?>" value="<?= Html::encode($body['client_address_1'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_address_2" class="form-label"><?= $translator->translate('i.street_address_2'); ?></label>
            <input type="text" class="form-control" name="client_address_2" id="client_address_2" placeholder="<?= $translator->translate('i.street_address_2'); ?>" value="<?= Html::encode($body['client_address_2'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_building_number" class="form-label"><?= $translator->translate('invoice.client.building.number'); ?></label>
            <input type="text" class="form-control" name="client_building_number" id="client_building_number" placeholder="<?= $translator->translate('invoice.client.building.number'); ?>" value="<?= Html::encode($body['client_building_number'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_city" class="form-label"><?= $translator->translate('i.city'); ?></label>
            <input type="text" class="form-control" name="client_city" id="client_city" placeholder="<?= $translator->translate('i.city'); ?>" value="<?= Html::encode($body['client_city'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_state" class="form-label"><?= $translator->translate('i.state'); ?></label>
            <input type="text" class="form-control" name="client_state" id="client_state" placeholder="<?= $translator->translate('i.state'); ?>" value="<?= Html::encode($body['client_state'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_zip" class="form-label"><?= $translator->translate('i.zip'); ?></label>
            <input type="text" class="form-control" name="client_zip" id="client_zip" placeholder="<?= $translator->translate('i.zip'); ?>" value="<?= Html::encode($body['client_zip'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_country" class="form-label"><?= $translator->translate('i.country'); ?></label>
            <div class="controls">
                <select name="client_country" id="client_country" class="form-control">
                    <?php foreach ($countries as $cldr => $country) { ?>
                        <option value="<?= $country; ?>"
                        <?php $s->check_select(($body['client_country'] ?? $client->getClient_country()), $country); ?>
                                ><?php echo $country ?></option>
                            <?php } ?>
                </select>
            </div>
        </div>
        <div class="mb-3 form-group">
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
        </div>
    </div>
</div>
<br>
<div class="card">
    <div class="card-header">
<?= $translator->translate('i.contact_information'); ?>
    </div>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <div class="mb-3 form-group">
            <label for="client_phone" class="form-label"><?= $translator->translate('i.phone'); ?></label>
            <input type="text" class="form-control" name="client_phone" id="client_phone" placeholder="<?= $translator->translate('i.phone'); ?>" value="<?= Html::encode($body['client_phone'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_fax" class="form-label"><?= $translator->translate('i.fax'); ?></label>
            <input type="text" class="form-control" name="client_fax" id="client_fax" placeholder="<?= $translator->translate('i.fax'); ?>" value="<?= Html::encode($body['client_fax'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_mobile" class="form-label"><?= $translator->translate('i.mobile'); ?></label>
            <input type="text" class="form-control" name="client_mobile" id="client_mobile" placeholder="<?= $translator->translate('i.mobile'); ?>" value="<?= Html::encode($body['client_mobile'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_email" class="form-label"><?= $translator->translate('i.email'); ?><span style="color:red">*</span></label>
            <input type="text" class="form-control" name="client_email" id="client_email" placeholder="<?= $translator->translate('i.email'); ?>" value="<?= Html::encode($body['client_email'] ?? '') ?>" required>
        </div>

        <div class="mb-3 form-group">
            <label for="client_web" class="form-label"><?= $translator->translate('i.web'); ?></label>
            <input type="text" class="form-control" name="client_web" id="client_web" placeholder="<?= $translator->translate('i.web'); ?>" value="<?= Html::encode($body['client_web'] ?? '') ?>">
        </div>
        <div class="mb-3 form-group">
            <div>
                <label for="postaladdress_id"><?= $translator->translate('invoice.client.postaladdress.available'); ?>: </label>
            </div>
            <div>
                <div class="input-group">
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
                  </div>
            </div>
        </div>
        <div class="mb-3 form-group">
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
        </div>
    </div>
</div>
<br>
<div class="card">
    <div class="card-header">
<?= $translator->translate('i.personal_information'); ?>
    </div>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <div class="mb-3 form-group">
            <label for="client_gender"  class="form-label"><?= $translator->translate('i.gender'); ?></label>
            <div class="controls">
                <select name="client_gender" id="client_gender"
                        class="form-control" data-minimum-results-for-search="Infinity">
                            <?php
                            $genders = [
                                $translator->translate('i.gender_male'),
                                $translator->translate('i.gender_female'),
                                $translator->translate('i.gender_other'),
                            ];
                            foreach ($genders as $key => $val) {
                                ?>
                        <option value=" <?php echo $key; ?>" <?php $s->check_select(Html::encode($body['client_gender'] ?? 0 ), $key) ?>>
                <?php echo $val; ?>
                        </option>
            <?php } ?>
                </select>
            </div>
        </div>
        <div class="mb-3 form-group has-feedback">
        <?php  
            $bdate = $datehelper->get_or_set_with_style($body['client_birthdate']);
            $InputDataBirthDate = new PureInputData(
                name: 'client_birthdate',
                value: null !== $bdate ? Html::encode($bdate instanceof \DateTimeImmutable ? $bdate->format($datehelper->style()) : $bdate) : null,
                label: $translator->translate('i.birthdate') . ' (' . $datehelper->display() . ')',
                hint: $translator->translate('invoice.client.birthdate.hint'),
                id: 'client_birthdate'  
            );
            echo Html::openTag('div',['class'=>'input-group']);
            echo Text::widget()
            ->inputData($InputDataBirthDate)
            ->addInputClass('form-control input-sm datepicker')
            ->addInputAttributes(['role'=>'presentation'])
            ->addInputAttributes(['autocomplete'=>'off'])
            ->addInputAttributes(['readonly'=>'readonly'])
            ->addInputAttributes(['required'=>'required'])
            ->render();
            echo Html::closeTag('div');
        ?>
            
        </div>
        <div class="mb-3 form-group">
            <label for="client_age" class="form-label"><?= $translator->translate('invoice.client.age'); ?></label>
            <input type="text" class="form-control" name="client_age" id="client_age" placeholder="<?= $translator->translate('invoice.client.age'); ?>" value="<?= Html::encode($body['client_age'] ?? '') ?>">
        </div>
        <div class="mb-3 form-group">
            <label for="client_avs" class="form-label"><?= $translator->translate('i.sumex_ssn'); ?></label>
            <input type="text" class="form-control" name="client_avs" id="client_avs" placeholder="<?= $translator->translate('i.sumex_ssn'); ?>" value="<?= Html::encode($body['client_avs'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">            
            <label for="client_insurednumber" class="form-label"><?= $translator->translate('i.sumex_insurednumber'); ?></label>
            <input type="text" class="form-control" name="client_insurednumber" id="client_insurednumber" placeholder="<?= $translator->translate('i.sumex_insurednumber'); ?>" value="<?= Html::encode($body['client_insurednumber'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_veka" class="form-label"><?= $translator->translate('i.sumex_veka'); ?></label>
            <input type="text" class="form-control" name="client_veka" id="client_veka" placeholder="<?= $translator->translate('i.sumex_veka'); ?>" value="<?= Html::encode($body['client_veka'] ?? '') ?>">
        </div>
        <div class="mb-3 form-group">
            <?php foreach ($custom_fields as $custom_field): ?>
                <?php
                if ($custom_field->getLocation() !== 3) {
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

<div class="card">
    <div class="card-header">
<?= $translator->translate('i.tax_information'); ?>
    </div>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <div class="mb-3 form-group">
            <label for="client_vat_id" class="form-label"><?= $translator->translate('i.vat_id'); ?></label>
            <input type="text" class="form-control" name="client_vat_id" id="client_vat_id" placeholder="<?= $translator->translate('i.vat_id'); ?>" value="<?= Html::encode($body['client_vat_id'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_tax_code" class="form-label"><?= $translator->translate('i.tax_code'); ?></label>
            <input type="text" class="form-control" name="client_tax_code" id="client_tax_code" placeholder="<?= $translator->translate('i.tax_code'); ?>" value="<?= Html::encode($body['client_tax_code'] ?? '') ?>">
        </div>
        <div class="mb-3 form-group">
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
        </div>
        <div class="form-group">
                            <?php if ($custom_fields): ?>
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <?= $translator->translate('i.custom_fields'); ?>
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
        </div>
    </div>
</div>
<?= Form::tag()->close() ?>