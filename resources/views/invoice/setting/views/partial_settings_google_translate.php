<?php
    declare(strict_types=1);
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= 'Google Translate'; ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[google_translate_json_filename]" <?= $s->where('google_translate_json_filename'); ?>>
                                <?= 'Google Translate Json Filename (eg. my_json_filename.json)'; ?>
                            </label>
                            <?php $body['settings[google_translate_json_filename]'] = $s->get_setting('google_translate_json_filename');?>
                            <input type="text" class="input-sm form-control" name="settings[google_translate_json_filename]" readonly="readonly"
                            id="settings[google_translate_json_filename]" value="<?= $s->get_setting('google_translate_json_filename'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="settings[google_translate_locale]" <?= $s->where('google_translate_locale'); ?>>
                                <?= 'Google Translate Locale'; ?>
                            </label>
                            <?php $body['settings[google_translate_locale]'] = $s->get_setting('google_translate_locale');?>
                            <select name="settings[google_translate_locale]" id="settings[google_translate_locale]"
                                class="form-control">
                                <option value=""><?= $s->trans('none'); ?></option>
                                <?php foreach ($locales as $key => $value) { ?>
                                    <option value="<?= $value; ?>"
                                        <?php $s->check_select($body['settings[google_translate_locale]'], $value); ?>>
                                        <?= $value; ?>
                                    </option>
                                    <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>    
