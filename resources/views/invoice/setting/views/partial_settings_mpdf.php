<?php
    declare(strict_types=1);
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">

        <div class="panel panel-default">
            <div class="panel-heading" >
                <label><?= $translator->translate('invoice.invoice.mpdf') ?></label>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[mpdf_ltr]" <?= $s->where('mpdf_ltr'); ?>>
                                <?= $translator->translate('invoice.invoice.mpdf.ltr'); ?>
                            </label>
                            <?php $body['settings[mpdf_ltr]'] = $s->get_setting('mpdf_ltr');?>
                            <select name="settings[mpdf_ltr]" id="settings[mpdf_ltr]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_ltr]'], '0'); ?>><?= $s->trans('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_ltr]'], '1'); ?>><?= $s->trans('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_cjk]">
                                <?= $translator->translate('invoice.invoice.mpdf.cjk'); ?>
                            </label>
                            <?php $body['settings[mpdf_cjk]'] = $s->get_setting('mpdf_cjk');?>
                            <select name="settings[mpdf_cjk]" id="settings[mpdf_cjk]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_cjk]'], '0'); ?>><?= $s->trans('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_cjk]'], '1'); ?>><?= $s->trans('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_auto_script_to_lang]">
                                <?= $translator->translate('invoice.invoice.mpdf.auto.script.to.lang'); ?>
                            </label>
                            <?php $body['settings[mpdf_auto_script_to_lang]'] = $s->get_setting('mpdf_auto_script_to_lang');?>
                            <select name="settings[mpdf_auto_script_to_lang]" id="settings[mpdf_auto_script_to_lang]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_auto_script_to_lang]'], '0'); ?>><?= $s->trans('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_auto_script_to_lang]'], '1'); ?>><?= $s->trans('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_auto_vietnamese]">
                                <?= $translator->translate('invoice.invoice.mpdf.auto.vietnamese'); ?>
                            </label>
                            <?php $body['settings[mpdf_auto_vietnamese]'] = $s->get_setting('mpdf_auto_vietnamese');?>
                            <select name="settings[mpdf_auto_vietnamese]" id="settings[mpdf_auto_vietnamese]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_auto_vietnamese]'], '0'); ?>><?= $s->trans('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_auto_vietnamese]'], '1'); ?>><?= $s->trans('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_allow_charset_conversion]">
                                <?= $translator->translate('invoice.invoice.mpdf.allow.charset.conversion'); ?>
                            </label>
                            <?php $body['settings[mpdf_allow_charset_conversion]'] = $s->get_setting('mpdf_allow_charset_conversion');?>
                            <select name="settings[mpdf_allow_charset_conversion]" id="settings[mpdf_allow_charset_conversion]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_allow_charset_conversion]'], '0'); ?>><?= $s->trans('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_allow_charset_conversion]'], '1'); ?>><?= $s->trans('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_auto_arabic]">
                                <?= $translator->translate('invoice.invoice.mpdf.auto.arabic'); ?>
                            </label>
                            <?php $body['settings[mpdf_auto_arabic]'] = $s->get_setting('mpdf_auto_arabic');?>
                            <select name="settings[mpdf_auto_arabic]" id="settings[mpdf_auto_arabic]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_auto_arabic]'], '0'); ?>><?= $s->trans('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_auto_arabic]'], '1'); ?>><?= $s->trans('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_auto_language_to_font]">
                                <?= $translator->translate('invoice.invoice.mpdf.auto.language.to.font'); ?>
                            </label>
                            <?php $body['settings[mpdf_auto_language_to_font]'] = $s->get_setting('mpdf_auto_language_to_font');?>
                            <select name="settings[mpdf_auto_language_to_font]" id="settings[mpdf_auto_language_to_font]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_auto_language_to_font]'], '0'); ?>><?= $s->trans('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_auto_language_to_font]'], '1'); ?>><?= $s->trans('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_show_image_errors]">
                                <?= $translator->translate('invoice.invoice.mpdf.show.image.errors'); ?>
                            </label>
                            <?php $body['settings[mpdf_show_image_errors]'] = $s->get_setting('mpdf_show_image_errors');?>
                            <select name="settings[mpdf_show_image_errors]" id="settings[mpdf_show_image_errors]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_show_image_errors]'], '0'); ?>><?= $s->trans('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_show_image_errors]'], '1'); ?>><?= $s->trans('yes'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
