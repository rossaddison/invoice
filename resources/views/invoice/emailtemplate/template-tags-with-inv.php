<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array                                  $custom_fields
 * @var array                                  $custom_fields['client_custom']
 * @var string                                 $template_tags_inv
 */
?>

<div class="panel panel-default">
    <div class="panel-heading"><?php echo $translator->translate('email.template.tags'); ?></div>
    <div class="panel-body">
        <p class="small"><?php echo $translator->translate('email.template.tags.instructions'); ?></p>
        <div class="form-group">
            <label for="tags_client"><?php echo $translator->translate('client'); ?></label>
            <select id="tags_client" class="taginv-select form-control">
                <option value="{{{client_name}}}">
                    <?php echo $translator->translate('client.name'); ?>
                </option>
                <option value="{{{client_surname}}}">
                    <?php echo $translator->translate('client.surname'); ?>
                </option>
                <optgroup label="<?php echo $translator->translate('address'); ?>">
                    <option value="{{{client_address_1}}}">
                        <?php echo $translator->translate('street.address'); ?>
                    </option>
                    <option value="{{{client_address_2}}}">
                        <?php echo $translator->translate('street.address.2'); ?>
                    </option>
                    <option value="{{{client_city}}}">
                        <?php echo $translator->translate('city'); ?>
                    </option>
                    <option value="{{{client_state}}}">
                        <?php echo $translator->translate('state'); ?>
                    </option>
                    <option value="{{{client_zip}}}">
                        <?php echo $translator->translate('zip'); ?>
                    </option>
                    <option value="{{{client_country}}}">
                        <?php echo $translator->translate('country'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?php echo $translator->translate('contact.information'); ?>">
                    <option value="{{{client_phone}}}">
                        <?php echo $translator->translate('phone'); ?>
                    </option>
                    <option value="{{{client_fax}}}">
                        <?php echo $translator->translate('fax'); ?>
                    </option>
                    <option value="{{{client_mobile}}}">
                        <?php echo $translator->translate('mobile'); ?>
                    </option>
                    <option value="{{{client_email}}}">
                        <?php echo $translator->translate('email'); ?>
                    </option>
                    <option value="{{{client_web}}}">
                        <?php echo $translator->translate('web.address'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?php echo $translator->translate('tax.information'); ?>">
                    <option value="{{{client_vat_id}}}">
                        <?php echo $translator->translate('vat.id'); ?>
                    </option>
                    <option value="{{{client_tax_code}}}">
                        <?php echo $translator->translate('tax.code'); ?>
                    </option>
                    <option value="{{{client_avs}}}">
                        <?php echo $translator->translate('sumex.ssn'); ?>
                    </option>
                    <option value="{{{client_insurednumber}}}">
                        <?php echo $translator->translate('sumex.insurednumber'); ?>
                    </option>
                    <option value="{{{client_weka}}}">
                        <?php echo $translator->translate('sumex.veka'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?php echo $translator->translate('custom.fields'); ?>">
                    <?php
                        /**
                         * @var App\Invoice\Entity\CustomField $custom
                         */
                        foreach ($custom_fields['client_custom'] as $custom) { ?>
                        <option value="{{{<?php echo 'cf_'.$custom->getId(); ?>}}}">
                            <?php echo ($custom->getLabel() ?? '#').' (ID '.$custom->getId().')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
        <div class="form-group">
            <label for="tags_user"><?php echo $translator->translate('user'); ?></label>
            <select id="tags_user" class="taginv-select form-control">
                <option value="{{{user_name}}}">
                    <?php echo $translator->translate('name'); ?>
                </option>
                <option value="{{{user_company}}}">
                    <?php echo $translator->translate('company'); ?>
                </option>
                <optgroup label="<?php echo $translator->translate('address'); ?>">
                    <option value="{{{user_address_1}}}">
                        <?php echo $translator->translate('street.address'); ?>
                    </option>
                    <option value="{{{user_address_2}}}">
                        <?php echo $translator->translate('street.address.2'); ?>
                    </option>
                    <option value="{{{user_city}}}">
                        <?php echo $translator->translate('city'); ?>
                    </option>
                    <option value="{{{user_state}}}">
                        <?php echo $translator->translate('state'); ?>
                    </option>
                    <option value="{{{user_zip}}}">
                        <?php echo $translator->translate('zip'); ?>
                    </option>
                    <option value="{{{user_country}}}">
                        <?php echo $translator->translate('country'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?php echo $translator->translate('contact.information'); ?>">
                    <option value="{{{user_phone}}}">
                        <?php echo $translator->translate('phone'); ?>
                    </option>
                    <option value="{{{user_fax}}}">
                        <?php echo $translator->translate('fax'); ?>
                    </option>
                    <option value="{{{user_mobile}}}">
                        <?php echo $translator->translate('mobile'); ?>
                    </option>
                    <option value="{{{user_email}}}">
                        <?php echo $translator->translate('email'); ?>
                    </option>
                    <option value="{{{user_web}}}">
                        <?php echo $translator->translate('web.address'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?php echo $translator->translate('sumex.information'); ?>">
                    <option value="{{{user_subscribernumber}}}">
                        <?php echo $translator->translate('user.subscriber.number'); ?>
                    </option>
                    <option value="{{{user_iban}}}">
                        <?php echo $translator->translate('user.iban'); ?>
                    </option>
                    <option value="{{{user_gln}}}">
                        <?php echo $translator->translate('gln'); ?>
                    </option>
                    <option value="{{{user_rcc}}}">
                        <?php echo $translator->translate('sumex.rcc'); ?>
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
        <?php echo $template_tags_inv; ?>
        <div class="form-group">
            <label for="tags_sumex"><?php echo $translator->translate('sumex'); ?></label>
            <select id="tags_sumex" class="taginv-select form-control">
                <option value="{{{sumex_reason}}}">
                    <?php echo $translator->translate('reason'); ?>
                </option>
                <option value="{{{sumex_diagnosis}}}">
                    <?php echo $translator->translate('sumex.diagnosis'); ?>
                </option>
                <option value="{{{sumex_observations}}}">
                    <?php echo $translator->translate('sumex.observations'); ?>
                </option>
                <option value="{{{sumex_treatmentstart}}}">
                    <?php echo $translator->translate('treatment.start'); ?>
                </option>
                <option value="{{{sumex_treatmentend}}}">
                    <?php echo $translator->translate('treatment.end'); ?>
                </option>
                <option value="{{{sumex_casedate}}}">
                    <?php echo $translator->translate('case.date'); ?>
                </option>
                <option value="{{{sumex_casenumber}}}">
                    <?php echo $translator->translate('case.number'); ?>
                </option>
            </select>
        </div>

    </div>
</div>