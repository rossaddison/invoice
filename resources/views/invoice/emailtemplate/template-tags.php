<?php
    declare(strict_types=1);
?>

<div class="panel panel-default">
    <div class="panel-heading"><?= $s->trans('email_template_tags'); ?></div>
    <div class="panel-body">
        <p class="small"><?= $s->trans('email_template_tags_instructions'); ?></p>
        <div class="form-group">
            <label for="tags_client"><?= $s->trans('client'); ?></label>
            <select id="tags_client" class="taginv-select form-control">
                <option value="{{{client_name}}}">
                    <?= $s->trans('client_name'); ?>
                </option>
                <option value="{{{client_surname}}}">
                    <?= $s->trans('client_surname'); ?>
                </option>
                <optgroup label="<?= $s->trans('address'); ?>">
                    <option value="{{{client_address_1}}}">
                        <?= $s->trans('street_address'); ?>
                    </option>
                    <option value="{{{client_address_2}}}">
                        <?= $s->trans('street_address_2'); ?>
                    </option>
                    <option value="{{{client_city}}}">
                        <?= $s->trans('city'); ?>
                    </option>
                    <option value="{{{client_state}}}">
                        <?= $s->trans('state'); ?>
                    </option>
                    <option value="{{{client_zip}}}">
                        <?= $s->trans('zip'); ?>
                    </option>
                    <option value="{{{client_country}}}">
                        <?= $s->trans('country'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $s->trans('contact_information'); ?>">
                    <option value="{{{client_phone}}}">
                        <?= $s->trans('phone'); ?>
                    </option>
                    <option value="{{{client_fax}}}">
                        <?= $s->trans('fax'); ?>
                    </option>
                    <option value="{{{client_mobile}}}">
                        <?= $s->trans('mobile'); ?>
                    </option>
                    <option value="{{{client_email}}}">
                        <?= $s->trans('email'); ?>
                    </option>
                    <option value="{{{client_web}}}">
                        <?= $s->trans('web_address'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $s->trans('tax_information'); ?>">
                    <option value="{{{client_vat_id}}}">
                        <?= $s->trans('vat_id'); ?>
                    </option>
                    <option value="{{{client_tax_code}}}">
                        <?= $s->trans('tax_code'); ?>
                    </option>
                    <option value="{{{client_avs}}}">
                        <?= $s->trans('sumex_ssn'); ?>
                    </option>
                    <option value="{{{client_insurednumber}}}">
                        <?= $s->trans('sumex_insurednumber'); ?>
                    </option>
                    <option value="{{{client_weka}}}">
                        <?= $s->trans('sumex_veka'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $s->trans('custom_fields'); ?>">
                    <?php foreach ($custom_fields['client_custom'] as $custom) { ?>
                        <option value="{{{<?= 'cf_' . $custom->getId(); ?>}}}">
                            <?= $custom->getLabel() . ' (ID ' . $custom->getId() . ')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
        <div class="form-group">
            <label for="tags_user"><?= $s->trans('user'); ?></label>
            <select id="tags_user" class="taginv-select form-control">
                <option value="{{{user_name}}}">
                    <?= $s->trans('name'); ?>
                </option>
                <option value="{{{user_company}}}">
                    <?= $s->trans('company'); ?>
                </option>
                <optgroup label="<?= $s->trans('address'); ?>">
                    <option value="{{{user_address_1}}}">
                        <?= $s->trans('street_address'); ?>
                    </option>
                    <option value="{{{user_address_2}}}">
                        <?= $s->trans('street_address_2'); ?>
                    </option>
                    <option value="{{{user_city}}}">
                        <?= $s->trans('city'); ?>
                    </option>
                    <option value="{{{user_state}}}">
                        <?= $s->trans('state'); ?>
                    </option>
                    <option value="{{{user_zip}}}">
                        <?= $s->trans('zip'); ?>
                    </option>
                    <option value="{{{user_country}}}">
                        <?= $s->trans('country'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $s->trans('contact_information'); ?>">
                    <option value="{{{user_phone}}}">
                        <?= $s->trans('phone'); ?>
                    </option>
                    <option value="{{{user_fax}}}">
                        <?= $s->trans('fax'); ?>
                    </option>
                    <option value="{{{user_mobile}}}">
                        <?= $s->trans('mobile'); ?>
                    </option>
                    <option value="{{{user_email}}}">
                        <?= $s->trans('email'); ?>
                    </option>
                    <option value="{{{user_web}}}">
                        <?= $s->trans('web_address'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $s->trans('sumex_information'); ?>">
                    <option value="{{{user_subscribernumber}}}">
                        <?= $s->trans('user_subscriber_number'); ?>
                    </option>
                    <option value="{{{user_iban}}}">
                        <?= $s->trans('user_iban'); ?>
                    </option>
                    <option value="{{{user_gln}}}">
                        <?= $s->trans('gln'); ?>
                    </option>
                    <option value="{{{user_rcc}}}">
                        <?= $s->trans('sumex_rcc'); ?>
                    </option>
                </optgroup>
                <!--
                <optgroup label="<//?//= $s->trans('custom_fields'); ?>">
                    <//?php// foreach ($custom_fields['user_custom'] as $custom) { ?>
                        <option value="{{{<//?//= 'cf_' . $custom->getCustom_field_id(); ?>}}}">
                            <//?//= $custom->getCustom_field_label() . ' (ID ' . $custom->getCustom_field_id() . ')'; ?>
                        </option>
                    <//?//php// } ?>
                </optgroup>
                -->
            </select>
        </div>
        <?= $template_tags_inv; ?>
        <?= $template_tags_quote; ?>
        <div class="form-group">
            <label for="tags_sumex"><?= $s->trans('invoice_sumex'); ?></label>
            <select id="tags_sumex" class="taginv-select form-control">
                <option value="{{{sumex_reason}}}">
                    <?= $s->trans('reason'); ?>
                </option>
                <option value="{{{sumex_diagnosis}}}">
                    <?= $s->trans('invoice_sumex_diagnosis'); ?>
                </option>
                <option value="{{{sumex_observations}}}">
                    <?= $s->trans('sumex_observations'); ?>
                </option>
                <option value="{{{sumex_treatmentstart}}}">
                    <?= $s->trans('treatment_start'); ?>
                </option>
                <option value="{{{sumex_treatmentend}}}">
                    <?= $s->trans('treatment_end'); ?>
                </option>
                <option value="{{{sumex_casedate}}}">
                    <?= $s->trans('case_date'); ?>
                </option>
                <option value="{{{sumex_casenumber}}}">
                    <?= $s->trans('case_number'); ?>
                </option>
            </select>
        </div>

    </div>
</div>