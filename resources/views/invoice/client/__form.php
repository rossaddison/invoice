<?php
declare(strict_types=1);

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
if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}
?>
<h1><?= Html::encode($title) ?></h1>

<?=
        Form::tag()
        ->post($urlGenerator->generate(...$action))
        ->enctypeMultipartFormData()
        ->csrf($csrf)
        ->id('clientForm')
        ->open()
?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= $s->trans('clients_form'); ?></h1>
    <?php
    echo $buttons;
    ?>
    <div class="mb-3 form-group btn-group-sm">
    </div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <?= $s->trans('personal_information'); ?>
        <div  class="p-2">
            <label for="client_active" class="control-label ">
                <?= $s->trans('active_client'); ?>
                <input id="client_active" name="client_active" type="checkbox" value="1"
                       <?php $s->check_select(Html::encode($body['client_active'] ?? ''), 1, '==', true) ?>>
            </label>
        </div>
    </div>
    <div class="row">
        <div class="mb-3 form-group">
            <label for="client_name" class="form-label"><?= $s->trans('client_name'); ?><span style="color:red">*</span></label>
            <input type="text" class="form-control" name="client_name" id="client_name"
                   placeholder="<?= $s->trans('client_name'); ?>" value="<?= Html::encode($body['client_name'] ?? '') ?>" required>
        </div>
        <div class="mb-3 form-group">
            <label for="client_surname" class="form-label"><?= $s->trans('client_surname'); ?></label>
            <input type="text" class="form-control" name="client_surname" id="client_surname" placeholder="<?= $s->trans('client_surname'); ?>" value="<?= Html::encode($body['client_surname'] ?? '') ?>">
        </div>
        <div class="mb-3 form-group">
            <label for="client_number" class="form-label"><?= $translator->translate('invoice.client.number'); ?></label>
            <input type="text" class="form-control" name="client_number" id="client_number" placeholder="<?= $translator->translate('invoice.client.number'); ?>" value="<?= Html::encode($body['client_number'] ?? '') ?>">
        </div>
        <div class="mb-3 form-group no-margin">
            <label for="client_language" class="form-label">
                <?php echo $s->trans('language'); ?>
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
        <?= $s->trans('address'); ?>
    </div>
    <div class="row">
        <div class="mb-3 form-group">
            <label for="client_address_1" class="form-label"><?= $s->trans('street_address'); ?></label>
            <input type="text" class="form-control" name="client_address_1" id="client_address_1" placeholder="<?= $s->trans('street_address'); ?>" value="<?= Html::encode($body['client_address_1'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_address_2" class="form-label"><?= $s->trans('street_address_2'); ?></label>
            <input type="text" class="form-control" name="client_address_2" id="client_address_2" placeholder="<?= $s->trans('street_address_2'); ?>" value="<?= Html::encode($body['client_address_2'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_building_number" class="form-label"><?= $translator->translate('invoice.client.building.number'); ?></label>
            <input type="text" class="form-control" name="client_building_number" id="client_building_number" placeholder="<?= $translator->translate('invoice.client.building.number'); ?>" value="<?= Html::encode($body['client_building_number'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_city" class="form-label"><?= $s->trans('city'); ?></label>
            <input type="text" class="form-control" name="client_city" id="client_city" placeholder="<?= $s->trans('city'); ?>" value="<?= Html::encode($body['client_city'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_state" class="form-label"><?= $s->trans('state'); ?></label>
            <input type="text" class="form-control" name="client_state" id="client_state" placeholder="<?= $s->trans('state'); ?>" value="<?= Html::encode($body['client_state'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_zip" class="form-label"><?= $s->trans('zip'); ?></label>
            <input type="text" class="form-control" name="client_zip" id="client_zip" placeholder="<?= $s->trans('zip'); ?>" value="<?= Html::encode($body['client_zip'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_country" class="form-label"><?= $s->trans('country'); ?></label>
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
<?= $s->trans('contact_information'); ?>
    </div>
    <div class="row">
        <div class="mb-3 form-group">
            <label for="client_phone" class="form-label"><?= $s->trans('phone'); ?></label>
            <input type="text" class="form-control" name="client_phone" id="client_phone" placeholder="<?= $s->trans('phone'); ?>" value="<?= Html::encode($body['client_phone'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_fax" class="form-label"><?= $s->trans('fax'); ?></label>
            <input type="text" class="form-control" name="client_fax" id="client_fax" placeholder="<?= $s->trans('fax'); ?>" value="<?= Html::encode($body['client_fax'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_mobile" class="form-label"><?= $s->trans('mobile'); ?></label>
            <input type="text" class="form-control" name="client_mobile" id="client_mobile" placeholder="<?= $s->trans('mobile'); ?>" value="<?= Html::encode($body['client_mobile'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_email" class="form-label"><?= $s->trans('email'); ?><span style="color:red">*</span></label>
            <input type="text" class="form-control" name="client_email" id="client_email" placeholder="<?= $s->trans('email'); ?>" value="<?= Html::encode($body['client_email'] ?? '') ?>" required>
        </div>

        <div class="mb-3 form-group">
            <label for="client_web" class="form-label"><?= $s->trans('web'); ?></label>
            <input type="text" class="form-control" name="client_web" id="client_web" placeholder="<?= $s->trans('web'); ?>" value="<?= Html::encode($body['client_web'] ?? '') ?>">
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
<?= $s->trans('personal_information'); ?>
    </div>
    <div class="row">
        <div class="mb-3 form-group">
            <label for="client_gender"  class="form-label"><?= $s->trans('gender'); ?></label>
            <div class="controls">
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
            </div>
        </div>
        <div class="mb-3 form-group has-feedback">
        <?php
        $bdate = $datehelper->get_or_set_with_style($body['client_birthdate']);
        ?>
            <label form-label for="client_birthdate"><?= $s->trans('birthdate') . ' (' . $datehelper->display() . ')'; ?></label>
            <div class="input-group">
                <input type="text" name="client_birthdate" id="client_birthdate" placeholder="<?= ' (' . $datehelper->display() . ')'; ?>"
                       class="form-control input-sm datepicker" readonly
                       value="<?= null !== $bdate ? Html::encode($bdate instanceof \DateTimeImmutable ? $bdate->format($datehelper->style()) : $bdate) : null; ?>" role="presentation" autocomplete="off">
                <span class="input-group-text">
                    <i class="fa fa-calendar fa-fw"></i>
                </span>
            </div>
        </div>
        <div class="mb-3 form-group">
            <label for="client_avs" class="form-label"><?= $s->trans('sumex_ssn'); ?></label>
            <input type="text" class="form-control" name="client_avs" id="client_avs" placeholder="<?= $s->trans('sumex_ssn'); ?>" value="<?= Html::encode($body['client_avs'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_insurednumber" class="form-label"><?= $s->trans('sumex_insurednumber'); ?></label>
            <input type="text" class="form-control" name="client_insurednumber" id="client_insurednumber" placeholder="<?= $s->trans('sumex_insurednumber'); ?>" value="<?= Html::encode($body['client_insurednumber'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_veka" class="form-label"><?= $s->trans('sumex_veka'); ?></label>
            <input type="text" class="form-control" name="client_veka" id="client_veka" placeholder="<?= $s->trans('sumex_veka'); ?>" value="<?= Html::encode($body['client_veka'] ?? '') ?>">
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
<?= $s->trans('tax_information'); ?>
    </div>
    <div class="row">
        <div class="mb-3 form-group">
            <label for="client_vat_id" class="form-label"><?= $s->trans('vat_id'); ?></label>
            <input type="text" class="form-control" name="client_vat_id" id="client_vat_id" placeholder="<?= $s->trans('vat_id'); ?>" value="<?= Html::encode($body['client_vat_id'] ?? '') ?>">
        </div>

        <div class="mb-3 form-group">
            <label for="client_tax_code" class="form-label"><?= $s->trans('tax_code'); ?></label>
            <input type="text" class="form-control" name="client_tax_code" id="client_tax_code" placeholder="<?= $s->trans('tax_code'); ?>" value="<?= Html::encode($body['client_tax_code'] ?? '') ?>">
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
        </div>
    </div>
</div>
<?= Form::tag()->close() ?>
