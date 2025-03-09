<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields
 * @var array $custom_fields['client_custom']
 * @var string $template_tags_inv
 */
?>

<div class="panel panel-default">
    <div class="panel-heading"><?= $translator->translate('i.email_template_tags'); ?></div>
    <div class="panel-body">
        <p class="small"><?= $translator->translate('i.email_template_tags_instructions'); ?></p>
        <div class="form-group">
            <label for="tags_client"><?= $translator->translate('i.client'); ?></label>
            <select id="tags_client" class="taginv-select form-control">
                <option value="{{{client_name}}}">
                    <?= $translator->translate('i.client_name'); ?>
                </option>
                <option value="{{{client_surname}}}">
                    <?= $translator->translate('i.client_surname'); ?>
                </option>
                <optgroup label="<?= $translator->translate('i.address'); ?>">
                    <option value="{{{client_address_1}}}">
                        <?= $translator->translate('i.street_address'); ?>
                    </option>
                    <option value="{{{client_address_2}}}">
                        <?= $translator->translate('i.street_address_2'); ?>
                    </option>
                    <option value="{{{client_city}}}">
                        <?= $translator->translate('i.city'); ?>
                    </option>
                    <option value="{{{client_state}}}">
                        <?= $translator->translate('i.state'); ?>
                    </option>
                    <option value="{{{client_zip}}}">
                        <?= $translator->translate('i.zip'); ?>
                    </option>
                    <option value="{{{client_country}}}">
                        <?= $translator->translate('i.country'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('i.contact_information'); ?>">
                    <option value="{{{client_phone}}}">
                        <?= $translator->translate('i.phone'); ?>
                    </option>
                    <option value="{{{client_fax}}}">
                        <?= $translator->translate('i.fax'); ?>
                    </option>
                    <option value="{{{client_mobile}}}">
                        <?= $translator->translate('i.mobile'); ?>
                    </option>
                    <option value="{{{client_email}}}">
                        <?= $translator->translate('i.email'); ?>
                    </option>
                    <option value="{{{client_web}}}">
                        <?= $translator->translate('i.web_address'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('i.tax_information'); ?>">
                    <option value="{{{client_vat_id}}}">
                        <?= $translator->translate('i.vat_id'); ?>
                    </option>
                    <option value="{{{client_tax_code}}}">
                        <?= $translator->translate('i.tax_code'); ?>
                    </option>
                    <option value="{{{client_avs}}}">
                        <?= $translator->translate('i.sumex_ssn'); ?>
                    </option>
                    <option value="{{{client_insurednumber}}}">
                        <?= $translator->translate('i.sumex_insurednumber'); ?>
                    </option>
                    <option value="{{{client_weka}}}">
                        <?= $translator->translate('i.sumex_veka'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('i.custom_fields'); ?>">
                    <?php
                       /**
                        * @var App\Invoice\Entity\CustomField $custom
                        */
                        foreach ($custom_fields['client_custom'] as $custom) { ?>
                        <option value="{{{<?= 'cf_' . $custom->getId(); ?>}}}">
                            <?= ($custom->getLabel() ?? '#'). ' (ID ' . $custom->getId() . ')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
        <div class="form-group">
            <label for="tags_user"><?= $translator->translate('i.user'); ?></label>
            <select id="tags_user" class="taginv-select form-control">
                <option value="{{{user_name}}}">
                    <?= $translator->translate('i.name'); ?>
                </option>
                <option value="{{{user_company}}}">
                    <?= $translator->translate('i.company'); ?>
                </option>
                <optgroup label="<?= $translator->translate('i.address'); ?>">
                    <option value="{{{user_address_1}}}">
                        <?= $translator->translate('i.street_address'); ?>
                    </option>
                    <option value="{{{user_address_2}}}">
                        <?= $translator->translate('i.street_address_2'); ?>
                    </option>
                    <option value="{{{user_city}}}">
                        <?= $translator->translate('i.city'); ?>
                    </option>
                    <option value="{{{user_state}}}">
                        <?= $translator->translate('i.state'); ?>
                    </option>
                    <option value="{{{user_zip}}}">
                        <?= $translator->translate('i.zip'); ?>
                    </option>
                    <option value="{{{user_country}}}">
                        <?= $translator->translate('i.country'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('i.contact_information'); ?>">
                    <option value="{{{user_phone}}}">
                        <?= $translator->translate('i.phone'); ?>
                    </option>
                    <option value="{{{user_fax}}}">
                        <?= $translator->translate('i.fax'); ?>
                    </option>
                    <option value="{{{user_mobile}}}">
                        <?= $translator->translate('i.mobile'); ?>
                    </option>
                    <option value="{{{user_email}}}">
                        <?= $translator->translate('i.email'); ?>
                    </option>
                    <option value="{{{user_web}}}">
                        <?= $translator->translate('i.web_address'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('i.sumex_information'); ?>">
                    <option value="{{{user_subscribernumber}}}">
                        <?= $translator->translate('i.user_subscriber_number'); ?>
                    </option>
                    <option value="{{{user_iban}}}">
                        <?= $translator->translate('i.user_iban'); ?>
                    </option>
                    <option value="{{{user_gln}}}">
                        <?= $translator->translate('i.gln'); ?>
                    </option>
                    <option value="{{{user_rcc}}}">
                        <?= $translator->translate('i.sumex_rcc'); ?>
                    </option>
                </optgroup>
                <!--
                <optgroup label="<//?//= $translator->translate('i.custom_fields'); ?>">
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
        <div class="form-group">
            <label for="tags_sumex"><?= $translator->translate('i.invoice_sumex'); ?></label>
            <select id="tags_sumex" class="taginv-select form-control">
                <option value="{{{sumex_reason}}}">
                    <?= $translator->translate('i.reason'); ?>
                </option>
                <option value="{{{sumex_diagnosis}}}">
                    <?= $translator->translate('i.invoice_sumex_diagnosis'); ?>
                </option>
                <option value="{{{sumex_observations}}}">
                    <?= $translator->translate('i.sumex_observations'); ?>
                </option>
                <option value="{{{sumex_treatmentstart}}}">
                    <?= $translator->translate('i.treatment_start'); ?>
                </option>
                <option value="{{{sumex_treatmentend}}}">
                    <?= $translator->translate('i.treatment_end'); ?>
                </option>
                <option value="{{{sumex_casedate}}}">
                    <?= $translator->translate('i.case_date'); ?>
                </option>
                <option value="{{{sumex_casenumber}}}">
                    <?= $translator->translate('i.case_number'); ?>
                </option>
            </select>
        </div>

    </div>
</div>