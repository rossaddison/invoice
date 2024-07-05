<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
    
    /**
     * @var App\Invoice\Setting\SettingRepository $s 
     * @var Yiisoft\Translator\TranslatorInterface $translator
     * @var array $body
     * @var array $languages
     * @var array $time_zones
     * @var array $first_days_of_weeks
     * @var array $date_formats
     * @var array $countries
     * @var array $gateway_currency_codes
     * @var array $number_formats
     * @var DateTime $current_date
     */
?>
<?= Html::openTag('div', ['class' => 'row']); ?>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.general'); ?>
            </div>
            <div class="panel-body">
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[install_test_data]" <?= $s->where('install_test_data'); ?>>
                                <?= $translator->translate('invoice.test.data.install'); ?>
                            </label>
                            <?php $body['settings[install_test_data]'] = $s->get_setting('install_test_data'); ?>
                            <select name="settings[install_test_data]" id="settings[install_test_data]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
                                        $s->check_select($body['settings[install_test_data]'], '1'); 
                                    ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[use_test_data]" <?= $s->where('use_test_data'); ?>">
                                <?= $translator->translate('invoice.test.data.use'); ?>
                            </label>
                            <?php $body['settings[use_test_data]'] = $s->get_setting('use_test_data'); ?>
                            <select name="settings[use_test_data]" id="settings[use_test_data]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
                                        $s->check_select($body['settings[use_test_data]'], '1'); 
                                    ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>   
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[default_language]" <?= $s->where('default_language'); ?> >
                                <?= $translator->translate('i.language'); ?>
                            </label>
                            <?php $body['settings[default_language]'] = $s->get_setting('default_language'); ?>
                            <select name="settings[default_language]" id="settings[default_language]" class="form-control">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var string $language
                                     */
                                    foreach ($languages as $language) { ?>
                                    <option value="<?= $language; ?>" <?php $s->check_select($body['settings[default_language]'], $language) ?>>
                                        <?= ucfirst($language); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>                    
                    <div class="col-xs-12 col-md-6">
                         <div class="form-group">
                            <label for="settings[time_zone]" <?= $s->where('time_zone'); ?>>
                                <?= $translator->translate('invoice.time.zone'); ?>
                            </label>
                            <?php   $body['settings[time_zone]'] = $s->get_setting('time_zone'); ?>
                            <select name="settings[time_zone]" id="settings[time_zone]" class="form-control">
                                 <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var string $value
                                     */
                                    foreach ($time_zones as $key => $value) { ?>
                                    <option value="<?=  $value; ?>"
                                        <?php  $s->check_select($body['settings[time_zone]'], $value); ?>>
                                        <?= $value; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class = "row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[first_day_of_week]" <?= $s->where('first_day_of_week'); ?>>
                                <?= $translator->translate('i.first_day_of_week'); ?>
                            </label>
                            <?php $body['settings[first_day_of_week]'] = $s->get_setting('first_day_of_week'); ?>
                            <select name="settings[first_day_of_week]" id="settings[first_day_of_week]"
                                class="form-control">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php 
                                    /**
                                     * @var string $first_day_of_week_id
                                     * @var string $first_day_of_week_name
                                     */
                                    foreach ($first_days_of_weeks as $first_day_of_week_id => $first_day_of_week_name) { ?>
                                    <option value="<?= $first_day_of_week_id; ?>"
                                        <?php
                                            $s->check_select($body['settings[first_day_of_week]'], $first_day_of_week_id); 
                                        ?>>
                                        <?= $first_day_of_week_name; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[date_format]" <?= $s->where('date_format'); ?>>
                                <?= $translator->translate('i.date_format'); ?>
                            </label>
                            <?php   $body['settings[date_format]'] = $s->get_setting('date_format'); ?>
                            <select name="settings[date_format]" id="settings[date_format]"
                                class="form-control">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var array $date_format
                                     * @var string $date_format['setting']
                                     */
                                    foreach ($date_formats as $date_format) { ?>
                                    <option value="<?= $date_format['setting']; ?>"
                                        <?php  $s->check_select($body['settings[date_format]'], $date_format['setting']); ?>>
                                        <?= $current_date->format($date_format['setting']); ?>
                                        (<?= $date_format['setting'] ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[default_country]" <?= $s->where('default_country'); ?>>
                                <?= $translator->translate('i.default_country'); ?>
                            </label>
                            <?php   $body['settings[default_country]'] = $s->get_setting('default_country'); ?>
                            <select name="settings[default_country]" id="settings[default_country]"
                                class="form-control">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var array $countries
                                     * @var string $cldr
                                     * @var string $country
                                     */
                                    foreach ($countries as $cldr => $country) { ?>
                                    <option value="<?= $cldr; ?>" 
                                        <?php
                                            $s->check_select($body['settings[default_country]'], $cldr); ?>>
                                        <?= $country ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="default_list_limit" <?= $s->where('default_list_limit'); ?>>
                                <?= $translator->translate('i.default_list_limit'); ?>
                            </label>
                            <?php $body['settings[default_list_limit]'] = $s->get_setting('default_list_limit'); ?>
                            <input type="number" name="settings[default_list_limit]" id="default_list_limit"
                                class="form-control" minlength="1" min="1" required
                                value="<?= $body['settings[default_list_limit]']; ?>">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.amount_settings'); ?>
            </div>
            <div class="panel-body">

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[currency_symbol]" <?= $s->where('currency_symbol'); ?>>
                                <?= $translator->translate('i.currency_symbol'); ?>
                            </label>
                            <?php 
                                $body['settings[currency_symbol]'] = $s->get_setting('currency_symbol');
                            ?>
                            <input type="text" name="settings[currency_symbol]" id="settings[currency_symbol]"
                                class="form-control"
                                value="<?= $body['settings[currency_symbol]']; ?>">
                        </div>
                    </div>

                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[currency_symbol_placement]" <?= $s->where('currency_symbol_placement'); ?>>
                                <?= $translator->translate('i.currency_symbol_placement'); ?>
                            </label>
                            <?php   $body['settings[currency_symbol_placement]'] = $s->get_setting('currency_symbol_placement'); ?>
                            <select name="settings[currency_symbol_placement]" id="settings[currency_symbol_placement]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value="before" 
                                    <?php   
                                        $s->check_select($body['settings[currency_symbol_placement]'], 'before'); 
                                    ?>>
                                    <?= $translator->translate('i.before_amount'); ?>
                                </option>
                                <option value="after" <?php $s->check_select($body['settings[currency_symbol_placement]'], 'after'); ?>>
                                    <?= $translator->translate('i.after_amount'); ?>
                                </option>
                                <option value="afterspace" <?php $s->check_select($body['settings[currency_symbol_placement]'], 'afterspace'); ?>>
                                    <?= $translator->translate('i.after_amount_space'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[currency_code]" <?= $s->where('currency_code'); ?>>
                                <?= $translator->translate('i.currency_code'); ?>
                            </label>
                            <?php $body['settings[currency_code]'] = $s->get_setting('currency_code'); ?>
                            <select name="settings[currency_code]"
                                id="settings[currency_code]"
                                class="input-sm form-control">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var string $key
                                     * @var string $val
                                     */
                                    foreach ($gateway_currency_codes as $key => $val) { ?>
                                    <option value="<?= $key; ?>"
                                        <?php
                                            $s->check_select($body['settings[currency_code]'], $key); 
                                        ?>>
                                        <?= $key; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[tax_rate_decimal_places]" <?= $s->where('tax_rate_decimal_places'); ?>>
                                <?= $translator->translate('i.tax_rate_decimal_places'); ?>
                            </label>
                            <?php   $body['settings[tax_rate_decimal_places]'] = $s->get_setting('tax_rate_decimal_places'); ?>
                            <select name="settings[tax_rate_decimal_places]" id="settings[tax_rate_decimal_places]" class="form-control">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <option value="2" 
                                    <?php 
                                        $s->check_select($body['settings[tax_rate_decimal_places]'], '2'); 
                                    ?>>
                                    2
                                </option>
                                <option value="3" 
                                    <?php
                                        $s->check_select($body['settings[tax_rate_decimal_places]'], '3');                                         
                                    ?>>
                                    3
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[number_format]" <?= $s->where('number_format'); ?>>
                                <?= $translator->translate('i.number_format'); ?>
                            </label>
                            <?php   $body['settings[number_format]'] = $s->get_setting('number_format'); ?>                            
                            <select name="settings[number_format]" id="settings[number_format]" 
                                class="form-control">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var string $key
                                     * @var array $value
                                     * @var string $value['label']
                                     */
                                    foreach ($number_formats as $key => $value) { ?>
                                    <option value="<?php print($key); ?>"
                                        <?php
                                            $s->check_select($body['settings[number_format]'], $value['label']); 
                                        ?>>
                                        <?= $translator->translate($value['label']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </div>


        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.dashboard'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[quote_overview_period]" <?= $s->where('quote_overview_period'); ?>>
                                <?= $translator->translate('i.quote_overview_period'); ?>
                            </label>
                            <?php $body['settings[quote_overview_period]'] = $s->get_setting('quote_overview_period'); ?>
                            <select name="settings[quote_overview_period]" id="settings[quote_overview_period]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <option value="this-month" <?php $s->check_select($body['settings[quote_overview_period]'], 'this-month'); ?>>
                                    <?= $translator->translate('i.this_month'); ?>
                                </option>
                                <option value="last-month" <?php $s->check_select($body['settings[quote_overview_period]'], 'last-month'); ?>>
                                    <?= $translator->translate('i.last_month'); ?>
                                </option>
                                <option value="this-quarter" <?php $s->check_select($body['settings[quote_overview_period]'], 'this-quarter'); ?>>
                                    <?= $translator->translate('i.this_quarter'); ?>
                                </option>
                                <option value="last-quarter" <?php $s->check_select($body['settings[quote_overview_period]'], 'last-quarter'); ?>>
                                    <?= $translator->translate('i.last_quarter'); ?>
                                </option>
                                <option value="this-year" <?php $s->check_select($body['settings[quote_overview_period]'], 'this-year'); ?>>
                                    <?= $translator->translate('i.this_year'); ?>
                                </option>
                                <option value="last-year" <?php $s->check_select($body['settings[quote_overview_period]'], 'last-year'); ?>>
                                    <?= $translator->translate('i.last_year'); ?>
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[invoice_overview_period]" <?= $s->where('invoice_overview_period'); ?>>
                                <?= $translator->translate('i.invoice_overview_period'); ?>
                            </label>
                            <?php $body['settings[invoice_overview_period]'] = $s->get_setting('invoice_overview_period'); ?>
                            <select name="settings[invoice_overview_period]" id="settings[invoice_overview_period]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <option value="this-month" <?php $s->check_select($body['settings[invoice_overview_period]'], 'this-month'); ?>>
                                    <?= $translator->translate('i.this_month'); ?>
                                </option>
                                <option value="last-month" <?php $s->check_select($body['settings[invoice_overview_period]'], 'last-month'); ?>>
                                    <?= $translator->translate('i.last_month'); ?>
                                </option>
                                <option value="this-quarter" <?php $s->check_select($body['settings[invoice_overview_period]'], 'this-quarter'); ?>>
                                    <?= $translator->translate('i.this_quarter'); ?>
                                </option>
                                <option value="last-quarter" <?php $s->check_select($body['settings[invoice_overview_period]'], 'last-quarter'); ?>>
                                    <?= $translator->translate('i.last_quarter'); ?>
                                </option>
                                <option value="this-year" <?php $s->check_select($body['settings[invoice_overview_period]'], 'this-year'); ?>>
                                    <?= $translator->translate('i.this_year'); ?>
                                </option>
                                <option value="last-year" <?php $s->check_select($body['settings[invoice_overview_period]'], 'last-year'); ?>>
                                    <?= $translator->translate('i.last_year'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="disable_quickactions" <?= $s->where('disable_quickactions'); ?>>
                                <?= $translator->translate('i.disable_quickactions'); ?>
                            </label>
                            <?php   $body['settings[disable_quickactions]'] = $s->get_setting('disable_quickactions'); ?>
                            <select name="settings[disable_quickactions]" class="form-control"
                                id="disable_quickactions" data-minimum-results-for-search="Infinity">
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" 
                                <?php
                                    $s->check_select($body['settings[disable_quickactions]'], '1'); 
                                ?>>
                                <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.interface'); ?>
            </div>
            <div class="panel-body">

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[disable_sidebar]" <?= $s->where('disable_sidebar'); ?>>
                                <?= $translator->translate('i.disable_sidebar'); ?>
                            </label>
                            <?php   $body['settings[disable_sidebar]'] = $s->get_setting('disable_sidebar'); ?>
                            <select name="settings[disable_sidebar]" id="settings[disable_sidebar]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
                                        $s->check_select($body['settings[disable_sidebar]'], '1'); 
                                    ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>                                  
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[custom_title]" <?= $s->where('custom_title'); ?>>
                                <?= $translator->translate('i.custom_title'); ?>
                            </label>
                            <?php $body['settings[custom_title]'] = $s->get_setting('custom_title'); ?>
                            <input type="text" name="settings[custom_title]" id="settings[custom_title]"
                                class="form-control"
                                value="<?= $body['settings[custom_title]']; ?>">
                        </div>
                    </div>
                </div>

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="monospace_amounts" <?= $s->where('monospace_amounts'); ?>>
                                <?= $translator->translate('i.monospaced_font_for_amounts'); ?>
                            </label>
                            <?php   $body['settings[monospace_amounts]'] = $s->get_setting('monospace_amounts'); ?>
                            <select name="settings[monospace_amounts]" class="form-control" id="monospace_amounts">
                                <option value="0"><?= $translator->translate('i.no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[monospace_amounts]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                            <p class="help-block">
                                <?= $translator->translate('i.example'); ?>:
                                <span style="font-family: Monaco, Lucida Console, monospace"><?= $s->format_currency(123456.78); ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[open_reports_in_new_tab]" <?= $s->where('open_reports_in_new_tab'); ?>>
                                <?= $translator->translate('i.open_reports_in_new_tab'); ?>
                            </label>
                            <?php  $body['settings[open_reports_in_new_tab]'] = $s->get_setting('open_reports_in_new_tab'); ?>
                            <select name="settings[open_reports_in_new_tab]" id="settings[open_reports_in_new_tab]" class="form-control">
                                <option value="0"><?= $translator->translate('i.no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[open_reports_in_new_tab]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.system_settings'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[bcc_mails_to_admin]" <?= $s->where('bcc_mails_to_admin'); ?>>
                                <?= $translator->translate('i.bcc_mails_to_admin'); ?>
                            </label>
                            <?php   $body['settings[bcc_mails_to_admin]'] = $s->get_setting('bcc_mails_to_admin'); ?>
                            <select name="settings[bcc_mails_to_admin]" id="settings[bcc_mails_to_admin]"
                                class="form-control">
                                <option value="0"><?= $translator->translate('i.no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[bcc_mails_to_admin]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[cron_key]" <?= $s->where('cron_key'); ?>>
                                <?= $translator->translate('i.cron_key'); ?>
                            </label>
                            <div class="input-group">
                                <input type="text" name="settings[cron_key]" id="settings[cron_key]" class="cron_key form-control" 
                                    value="<?= (string)($body['settings[cron_key]'] ?? $s->get_setting('cron_key')); ?>">
                                <div class="input-group-text">
                                    <?php
                                        /**
                                         * @see ..\src\Invoice\Asset\rebuild-1.13\js\setting.js 
                                         * @see $(document).on('click', '#btn_generate_cron_key', function ()
                                         */
                                    ?>
                                    <button id="btn_generate_cron_key" type="button" class="btn_generate_cron_key btn btn-primary btn-block">
                                        <i class="fa fa-recycle fa-margin"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
