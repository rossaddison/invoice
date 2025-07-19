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
    <div class="panel-heading"><?= $translator->translate('email.template.tags'); ?></div>
    <div class="panel-body">
        <p class="small"><?= $translator->translate('email.template.tags.instructions'); ?></p>
        <div class="form-group">
            <label for="tags_client"><?= $translator->translate('client'); ?></label>
            <select id="tags_client" class="taginv-select form-control">
                <option value="{{{client_name}}}">
                    <?= $translator->translate('client.name'); ?>
                </option>
                <option value="{{{client_surname}}}">
                    <?= $translator->translate('client.surname'); ?>
                </option>
                <optgroup label="<?= $translator->translate('address'); ?>">
                    <option value="{{{client_address_1}}}">
                        <?= $translator->translate('street.address'); ?>
                    </option>
                    <option value="{{{client_address_2}}}">
                        <?= $translator->translate('street.address.2'); ?>
                    </option>
                    <option value="{{{client_city}}}">
                        <?= $translator->translate('city'); ?>
                    </option>
                    <option value="{{{client_state}}}">
                        <?= $translator->translate('state'); ?>
                    </option>
                    <option value="{{{client_zip}}}">
                        <?= $translator->translate('zip'); ?>
                    </option>
                    <option value="{{{client_country}}}">
                        <?= $translator->translate('country'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('contact.information'); ?>">
                    <option value="{{{client_phone}}}">
                        <?= $translator->translate('phone'); ?>
                    </option>
                    <option value="{{{client_fax}}}">
                        <?= $translator->translate('fax'); ?>
                    </option>
                    <option value="{{{client_mobile}}}">
                        <?= $translator->translate('mobile'); ?>
                    </option>
                    <option value="{{{client_email}}}">
                        <?= $translator->translate('email'); ?>
                    </option>
                    <option value="{{{client_web}}}">
                        <?= $translator->translate('web.address'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('tax.information'); ?>">
                    <option value="{{{client_vat_id}}}">
                        <?= $translator->translate('vat.id'); ?>
                    </option>
                    <option value="{{{client_tax_code}}}">
                        <?= $translator->translate('tax.code'); ?>
                    </option>
                    <option value="{{{client_avs}}}">
                        <?= $translator->translate('sumex.ssn'); ?>
                    </option>
                    <option value="{{{client_insurednumber}}}">
                        <?= $translator->translate('sumex.insurednumber'); ?>
                    </option>
                    <option value="{{{client_weka}}}">
                        <?= $translator->translate('sumex.veka'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('custom.fields'); ?>">
                    <?php
                       /**
                        * @var App\Invoice\Entity\CustomField $custom
                        */
                        foreach ($custom_fields['client_custom'] as $custom) { ?>
                        <option value="{{{<?= 'cf_' . $custom->getId(); ?>}}}">
                            <?= ($custom->getLabel() ?? '#') . ' (ID ' . $custom->getId() . ')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
        <div class="form-group">
            <label for="tags_user"><?= $translator->translate('user'); ?></label>
            <select id="tags_user" class="taginv-select form-control">
                <option value="{{{user_name}}}">
                    <?= $translator->translate('name'); ?>
                </option>
                <option value="{{{user_company}}}">
                    <?= $translator->translate('company'); ?>
                </option>
                <optgroup label="<?= $translator->translate('address'); ?>">
                    <option value="{{{user_address_1}}}">
                        <?= $translator->translate('street.address'); ?>
                    </option>
                    <option value="{{{user_address_2}}}">
                        <?= $translator->translate('street.address.2'); ?>
                    </option>
                    <option value="{{{user_city}}}">
                        <?= $translator->translate('city'); ?>
                    </option>
                    <option value="{{{user_state}}}">
                        <?= $translator->translate('state'); ?>
                    </option>
                    <option value="{{{user_zip}}}">
                        <?= $translator->translate('zip'); ?>
                    </option>
                    <option value="{{{user_country}}}">
                        <?= $translator->translate('country'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('contact.information'); ?>">
                    <option value="{{{user_phone}}}">
                        <?= $translator->translate('phone'); ?>
                    </option>
                    <option value="{{{user_fax}}}">
                        <?= $translator->translate('fax'); ?>
                    </option>
                    <option value="{{{user_mobile}}}">
                        <?= $translator->translate('mobile'); ?>
                    </option>
                    <option value="{{{user_email}}}">
                        <?= $translator->translate('email'); ?>
                    </option>
                    <option value="{{{user_web}}}">
                        <?= $translator->translate('web.address'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('sumex.information'); ?>">
                    <option value="{{{user_subscribernumber}}}">
                        <?= $translator->translate('user.subscriber.number'); ?>
                    </option>
                    <option value="{{{user_iban}}}">
                        <?= $translator->translate('user.iban'); ?>
                    </option>
                    <option value="{{{user_gln}}}">
                        <?= $translator->translate('gln'); ?>
                    </option>
                    <option value="{{{user_rcc}}}">
                        <?= $translator->translate('sumex.rcc'); ?>
                    </option>
                </optgroup>
                <!--
                <optgroup label="<//?//= $translator->translate('custom.fields'); ?>">
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
            <label for="tags_sumex"><?= $translator->translate('sumex'); ?></label>
            <select id="tags_sumex" class="taginv-select form-control">
                <option value="{{{sumex_reason}}}">
                    <?= $translator->translate('reason'); ?>
                </option>
                <option value="{{{sumex_diagnosis}}}">
                    <?= $translator->translate('sumex.diagnosis'); ?>
                </option>
                <option value="{{{sumex_observations}}}">
                    <?= $translator->translate('sumex.observations'); ?>
                </option>
                <option value="{{{sumex_treatmentstart}}}">
                    <?= $translator->translate('treatment.start'); ?>
                </option>
                <option value="{{{sumex_treatmentend}}}">
                    <?= $translator->translate('treatment.end'); ?>
                </option>
                <option value="{{{sumex_casedate}}}">
                    <?= $translator->translate('case.date'); ?>
                </option>
                <option value="{{{sumex_casenumber}}}">
                    <?= $translator->translate('case.number'); ?>
                </option>
            </select>
        </div>

    </div>
</div>