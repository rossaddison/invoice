<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
?>
<?= Html::openTag('div', ['class' => 'row']); ?>
    <div class="col-xs-12 col-md-8 col-md-offset-2">

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= 'Google Translate'; ?>
            </div>
            <div class="panel-body">
                <p><b>Objective:</b> Rebuild a \www\invoice\resources\messages\en\app.php language file and insert it into e.g. pt_BR\app.php. </p>
                <p><b>Problem:</b> This en\app.php file is too big for Google to Translate. </p>
                <p><b>Step 1:</b> Copy the 'i.account_information' .. 'i.false' to src\Invoice\English\ip_lang.php file</p>
                <p><b>Step 2:</b> Copy the 'g.online_payment' .. 'g.online_payment_3dauth_redirect' to src\Invoice\English\gateway_lang.php file</p>
                <p><b>Step 3:</b> Copy the 'invoice.add' .. 'validator.user.exist.not' to src\Invoice\English\latest_lang.php file</p>
                <p><b>Step 4:</b> Run the Generator ... Translate English sub programs to translate the above files from English into the language of your choice.</p>
                <p><b>Step 5:</b> You will have to combine these 3 parts into one array called app.php in the language of your choice and place it into a suitable folder in Step 1's folder.</p>
                <p><b>Step 6:</b> Adjust the \resources\views\layout files.</p>
                <p><b>Step 7:</b> Create a suitable \invoice\src\Invoice\Asset\i18nAsset file under this folder.</p>
                <p><b>Step 8:</b> Create a separate folder under \invoice\src\Invoice\Language. This will be used by View...Settings...General...Language</p>
                <p><b>Step 9:</b> Adjust the SettingsRepository locale_language_array() to include your language. e.g. 'pt-BR'</p>
                <p><b>Step 10:</b> Adjust the config\web\params.php locales array to include your language. e.g. 'pt-BR'</p>
                
            </div>
            <div class="panel-body">
                <p><i class="bi bi-link"></i> <a href="https://curl.haxx.se/ca/cacert.pem" target="_blank">https://curl.haxx.se/ca/cacert.pem</a></p>
                <p><i class="bi bi-link"></i><a href="https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?supportedpurview=project" target="_blank">https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?supportedpurview=project</a></p> 
                <p><i class="bi bi-link"></i><?php echo php_ini_loaded_file(); ?></p>
            </div>    
            <div class="panel-body">
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[google_translate_json_filename]" <?= $s->where('google_translate_json_filename'); ?>> <i class="bi bi-info-circle"></i>
                                <?= 'Google Translate Json Filename (eg. my_json_filename.json)'; ?>
                            </label>
                            <?php $body['settings[google_translate_json_filename]'] = $s->get_setting('google_translate_json_filename');?>
                            <input type="text" class="input-sm form-control" name="settings[google_translate_json_filename]" 
                            id="settings[google_translate_json_filename]" value="<?= $s->get_setting('google_translate_json_filename'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="settings[google_translate_locale]" <?= $s->where('google_translate_locale'); ?>>
                                <?= 'Google Translate Locale'; ?>
                            </label>
                            <?php $body['settings[google_translate_locale]'] = $s->get_setting('google_translate_locale');?>
                            <select name="settings[google_translate_locale]" id="settings[google_translate_locale]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
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
