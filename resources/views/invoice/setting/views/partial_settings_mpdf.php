<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */
?>
<?= Html::openTag('div', ['class' => 'row']); ?>
    <div class="col-xs-12 col-md-8 col-md-offset-2">

        <div class="panel panel-default">
            <div class="panel-heading" >
                <h6><?= $translator->translate('mpdf') ?></h6>
            </div>
            <div class="panel-body">

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[mpdf_ltr]" <?= $s->where('mpdf_ltr'); ?>>
                                <?= $translator->translate('mpdf.ltr'); ?>
                            </label>
                            <?php $body['settings[mpdf_ltr]'] = $s->getSetting('mpdf_ltr');?>
                            <select name="settings[mpdf_ltr]" id="settings[mpdf_ltr]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_ltr]'], '0'); ?>><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_ltr]'], '1'); ?>><?= $translator->translate('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_cjk]">
                                <?= $translator->translate('mpdf.cjk'); ?>
                            </label>
                            <?php $body['settings[mpdf_cjk]'] = $s->getSetting('mpdf_cjk');?>
                            <select name="settings[mpdf_cjk]" id="settings[mpdf_cjk]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_cjk]'], '0'); ?>><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_cjk]'], '1'); ?>><?= $translator->translate('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_auto_script_to_lang]">
                                <?= $translator->translate('mpdf.auto.script.to.lang'); ?>
                            </label>
                            <?php $body['settings[mpdf_auto_script_to_lang]'] = $s->getSetting('mpdf_auto_script_to_lang');?>
                            <select name="settings[mpdf_auto_script_to_lang]" id="settings[mpdf_auto_script_to_lang]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_auto_script_to_lang]'], '0'); ?>><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_auto_script_to_lang]'], '1'); ?>><?= $translator->translate('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_auto_vietnamese]">
                                <?= $translator->translate('mpdf.auto.vietnamese'); ?>
                            </label>
                            <?php $body['settings[mpdf_auto_vietnamese]'] = $s->getSetting('mpdf_auto_vietnamese');?>
                            <select name="settings[mpdf_auto_vietnamese]" id="settings[mpdf_auto_vietnamese]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_auto_vietnamese]'], '0'); ?>><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_auto_vietnamese]'], '1'); ?>><?= $translator->translate('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_allow_charset_conversion]">
                                <?= $translator->translate('mpdf.allow.charset.conversion'); ?>
                            </label>
                            <?php $body['settings[mpdf_allow_charset_conversion]'] = $s->getSetting('mpdf_allow_charset_conversion');?>
                            <select name="settings[mpdf_allow_charset_conversion]" id="settings[mpdf_allow_charset_conversion]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_allow_charset_conversion]'], '0'); ?>><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_allow_charset_conversion]'], '1'); ?>><?= $translator->translate('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_auto_arabic]">
                                <?= $translator->translate('mpdf.auto.arabic'); ?>
                            </label>
                            <?php $body['settings[mpdf_auto_arabic]'] = $s->getSetting('mpdf_auto_arabic');?>
                            <select name="settings[mpdf_auto_arabic]" id="settings[mpdf_auto_arabic]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_auto_arabic]'], '0'); ?>><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_auto_arabic]'], '1'); ?>><?= $translator->translate('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_auto_language_to_font]">
                                <?= $translator->translate('mpdf.auto.language.to.font'); ?>
                            </label>
                            <?php $body['settings[mpdf_auto_language_to_font]'] = $s->getSetting('mpdf_auto_language_to_font');?>
                            <select name="settings[mpdf_auto_language_to_font]" id="settings[mpdf_auto_language_to_font]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_auto_language_to_font]'], '0'); ?>><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_auto_language_to_font]'], '1'); ?>><?= $translator->translate('yes'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[mpdf_show_image_errors]">
                                <?= $translator->translate('mpdf.show.image.errors'); ?>
                            </label>
                            <?php $body['settings[mpdf_show_image_errors]'] = $s->getSetting('mpdf_show_image_errors');?>
                            <select name="settings[mpdf_show_image_errors]" id="settings[mpdf_show_image_errors]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[mpdf_show_image_errors]'], '0'); ?>><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[mpdf_show_image_errors]'], '1'); ?>><?= $translator->translate('yes'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
