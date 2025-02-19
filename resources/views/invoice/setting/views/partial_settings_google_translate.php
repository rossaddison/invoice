<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
    
    /**
     * @var App\Invoice\Setting\SettingRepository $s 
     * @var Yiisoft\Translator\TranslatorInterface $translator
     * @var array $body
     * @var array $locales
     */
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= 'Google Translate'; ?>
            </div>
            <div class="panel-body">
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[google_translate_json_filename]" <?= $s->where('google_translate_json_filename'); ?>> <i class="bi bi-info-circle"></i>
                                <?= 'Google Translate Json Filename (eg. my_json_filename.json)'; ?>
                            </label>
                            <?php $body['settings[google_translate_json_filename]'] = $s->getSetting('google_translate_json_filename');?>
                            <input type="text" class="input-sm form-control" name="settings[google_translate_json_filename]" 
                            id="settings[google_translate_json_filename]" value="<?= $s->getSetting('google_translate_json_filename'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="settings[google_translate_locale]" <?= $s->where('google_translate_locale'); ?>>
                                <?= 'Google Translate Locale'; ?>
                            </label>
                            <?php $body['settings[google_translate_locale]'] = $s->getSetting('google_translate_locale');?>
                            <select name="settings[google_translate_locale]" id="settings[google_translate_locale]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var string $key
                                     * @var string $value
                                     */
                                    foreach ($locales as $key => $value) { ?>
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
            <div class="panel-body">
                <p><b>Objective:</b> Rebuild a \www\invoice\resources\messages\en\app.php language file and insert it into e.g. <a href="https://github.com/rossaddison/invoice/commit/28188010c7965092f92484871712bf8347f0f5ed">zu_ZA\app.php</a></p>
                <p><b>Problem:</b> This en\app.php file is too big for Google to Translate. </p>
                <p><b>Step 1:</b> Copy the <pre>'g.online_payment' .. 'g.online_payment_3dauth_redirect'</pre> to <pre>src\Invoice\Language\English\<b>gateway_lang.php</b></pre></p>
                <p><b>Step 2:</b> Copy the <pre>'i.account_information' .. 'i.false'</pre> to <pre>src\Invoice\Language\English\<b>ip_lang.php</b></pre></p>
                <p><b>Step 3:</b> Copy the <pre>'invoice.add' .. 'validator.user.exist.not'</pre> to <pre>src\Invoice\Language\English\<b>latest_lang.php</b></pre></p>
                <p><b>Step 4:</b> Copy the <pre>'site.soletrader.about.we' .. 'site.soletrader.contact.phone'</pre> to <pre>src\Invoice\Language\English\<b>common_lang.php</b></pre></p>
                <p><b>Step 5:</b> Optional: Copy any loose <pre>'x.x.x.x' .. 'x.x.x.y'</pre> to <pre>src\Invoice\Language\English\<b>any_lang.php</b></pre></p>
                <p><b>Step 6:</b> Run the Generator ... Translate English sub programs to translate the above files from English into the language of your choice into<pre><h6>...\resources\views\invoice\generator\output_overwrite</h6></pre>.</p>
                <p><b>Step 7:</b> You will have to combine these 3 parts into one array called <code>app.php</code> in the language of your choice and place it into a folder <code>..resources\messages\{locale}</code> folder</p>
                <p><b>Step 8:</b> Adjust the <code>\resources\views\layout</code> files.</p>
                <p><b>Step 9:</b> Create a suitable <code>\invoice\src\Invoice\Asset\i18nAsset</code> file under this folder.</p>
                <p><b>Step 10:</b> Create a separate folder under <code>\invoice\src\Invoice\Language</code>. This will be used by View...Settings...General...Language</p>
                <p><b>Step 11:</b> Adjust the <code>SettingsRepository locale_language_array()</code> to include your language. e.g. 'pt-BR' and also the <code>locales</code> function.</p>
                <p><b>Step 12:</b> Adjust the <code>config\web\params.php</code> locales array to include your language. e.g. 'pt-BR'</p>
            </div>
            <div class="panel-body">
                <p><i class="bi bi-link"></i> <a href="https://curl.haxx.se/ca/cacert.pem" target="_blank">https://curl.haxx.se/ca/cacert.pem</a></p>
                <p><i class="bi bi-link"></i><a href="https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?supportedpurview=project" target="_blank">https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?supportedpurview=project</a></p> 
                <p><i class="bi bi-link"></i><?php echo php_ini_loaded_file(); ?></p>
            </div> 
            <div class="panel-body">
                <p class="demoTitle">&nbsp; &nbsp;</p>
                <p>GeneratorController includes a function <em>google_translate_lang</em>. This function takes the English i<em>p_lang</em> array or <em>gateway_lang</em> located in <em>src/Invoice/Language/English</em> and translates it into the chosen locale (Settings...View...Google Translate) outputting it to <em>resources/views/generator/output_overwrite</em>.</p>
                <p><strong>Step 1:</strong> <br />Download <code>https://curl.haxx.se/ca/cacert.pem</code> into active <code>c:\wamp64\bin\php\php8.1.12</code> folder</p>
                <p><strong>Step 2:</strong> <br />Select your project that you created under <code>https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?supportedpurview=project</code></p>
                <p><strong>Step 3:</strong> <br />Click on Actions icon and select Manage Keys.</p>
                <p><strong>Step 4:</strong> <br />Add Key.</p>
                <p><strong>Step 5:</strong> <br />Choose the Json File option and Download the file to <code>src/Invoice/Google_translate_unique_folder</code>.</p>
                <p><strong>Step 6:</strong> <br />You will have to enable the Cloud Translation API and provide your billing details. You will be charged 0 currency.</p>
                <p><strong>Step 7:</strong> <br />Adjust the php.ini [apache_module] by means of the wampserver icon or by clicking on the symlink in the directory.</p>
                <p><strong>Step 8:</strong> <br />The symlink file points to <code>C:\wamp64\bin\php\php8.3.16\phpForApache.ini</code> Adjust this manually at line 1947 [curl] with eg. <code>"c:/wamp64/bin/php/php8.3.16/cacert.pem"</code> Note the forward slashes.</p>
                <p><strong>Step 9:</strong> <br />Reboot your server.</p>
                <p><strong>Step 10:</strong> <br />After generating the file, move the file from <code>views/generator/output_overwrite to e.g. src/Invoice/Language/{your language}</code>. Use all three array sections to build one language array file i.e. app.php</p>
                <p>&nbsp;</p>
            </div>             
        </div>
    </div>
</div>    
