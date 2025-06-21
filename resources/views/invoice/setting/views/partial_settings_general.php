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
<div class = 'row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('general'); ?>
            </div>
            <div class="panel-body">
                <div class = 'row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[stop_logging_in]">
                                <?= $translator->translate('stop.logging.in'); ?>
                            </label>
                            <?php $body['settings[stop_logging_in]'] = $s->getSetting('stop_logging_in'); ?>
                            <select name="settings[stop_logging_in]" id="settings[stop_logging_in]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
                                        $s->check_select($body['settings[stop_logging_in]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[stop_signing_up]">
                                <?= $translator->translate('stop.signing.up'); ?>
                            </label>
                            <?php $body['settings[stop_signing_up]'] = $s->getSetting('stop_signing_up'); ?>
                            <select name="settings[stop_signing_up]" id="settings[stop_signing_up]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
    $s->check_select($body['settings[stop_signing_up]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div> 
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[install_test_data]" <?= $s->where('install_test_data'); ?>>
                                <?= $translator->translate('test.data.install'); ?>
                            </label>
                            <?php $body['settings[install_test_data]'] = $s->getSetting('install_test_data'); ?>
                            <select name="settings[install_test_data]" id="settings[install_test_data]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
    $s->check_select($body['settings[install_test_data]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[use_test_data]" <?= $s->where('use_test_data'); ?>">
                                <?= $translator->translate('test.data.use'); ?>
                            </label>
                            <?php $body['settings[use_test_data]'] = $s->getSetting('use_test_data'); ?>
                            <select name="settings[use_test_data]" id="settings[use_test_data]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
    $s->check_select($body['settings[use_test_data]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>   
                    <div class="col-xs-12 col-md-6">
                         <div class="form-group">
                            <label for="settings[default_language]" <?= $s->where('default_language'); ?> >
                                <?= $translator->translate('language'); ?>
                            </label>
                            <?php $body['settings[default_language]'] = $s->getSetting('default_language'); ?>
                            <select name="settings[default_language]" id="settings[default_language]" class="form-control">
                                <option value="0"><?= $translator->translate('none'); ?></option>
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
                                <?= $translator->translate('time.zone'); ?>
                            </label>
                            <?php   $body['settings[time_zone]'] = $s->getSetting('time_zone'); ?>
                            <select name="settings[time_zone]" id="settings[time_zone]" class="form-control">
                                 <option value="0"><?= $translator->translate('none'); ?></option>
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
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[default_country]" <?= $s->where('default_country'); ?>>
                                <?= $translator->translate('default.country'); ?>
                            </label>
                            <?php   $body['settings[default_country]'] = $s->getSetting('default_country'); ?>
                            <select name="settings[default_country]" id="settings[default_country]"
                                class="form-control">
                                <option value="0"><?= $translator->translate('none'); ?></option>
                                <option value=""><?= $translator->translate('none'); ?></option>
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
                                <?= $translator->translate('default.list.limit'); ?>
                            </label>
                            <?php $body['settings[default_list_limit]'] = $s->getSetting('default_list_limit'); ?>
                            <input type="number" name="settings[default_list_limit]" id="default_list_limit"
                                class="form-control" minlength="1" min="1" required
                                value="<?= $body['settings[default_list_limit]']; ?>">
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[disable_flash_messages_quote]">
                                <?= $translator->translate('quote.disable.flash.messages'); ?>
                            </label>
                            <?php $body['settings[disable_flash_messages_quote]'] = $s->getSetting('disable_flash_messages_quote'); ?>
                            <select name="settings[disable_flash_messages_quote]" id="settings[disable_flash_messages_quote]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
    $s->check_select($body['settings[disable_flash_messages_quote]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>    
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[disable_flash_messages_inv]">
                                <?= $translator->translate('disable.flash.messages'); ?>
                            </label>
                            <?php $body['settings[disable_flash_messages_inv]'] = $s->getSetting('disable_flash_messages_inv'); ?>
                            <select name="settings[disable_flash_messages_inv]" id="settings[disable_flash_messages_inv]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
    $s->check_select($body['settings[disable_flash_messages_inv]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>    
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[signup_automatically_assign_client]">
                                <?= $translator->translate('assign.client.on.signup'); ?>
                            </label>
                            <?php $body['settings[signup_automatically_assign_client]'] = $s->getSetting('signup_automatically_assign_client'); ?>
                            <select name="settings[signup_automatically_assign_client]" id="settings[signup_automatically_assign_client]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
    $s->check_select($body['settings[signup_automatically_assign_client]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>    
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[signup_default_age_minimum_eighteen]">
                                <?= $translator->translate('assign.client.on.signup.default.age.minimum.eighteen'); ?>
                            </label>
                            <?php $body['settings[signup_default_age_minimum_eighteen]'] = $s->getSetting('signup_default_age_minimum_eighteen'); ?>
                            <select name="settings[signup_default_age_minimum_eighteen]" id="settings[signup_default_age_minimum_eighteen]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
    $s->check_select($body['settings[signup_default_age_minimum_eighteen]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>    
                        </div>
                    </div>
                    
                </div>

            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('amount.settings'); ?>
            </div>
            <div class="panel-body">

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[currency_symbol]" <?= $s->where('currency_symbol'); ?>>
                                <?= $translator->translate('currency.symbol'); ?>
                            </label>
                            <?php
                                $body['settings[currency_symbol]'] = $s->getSetting('currency_symbol');
?>
                            <input type="text" name="settings[currency_symbol]" id="settings[currency_symbol]"
                                class="form-control"
                                value="<?= $body['settings[currency_symbol]']; ?>">
                        </div>
                    </div>

                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[currency_symbol_placement]" <?= $s->where('currency_symbol_placement'); ?>>
                                <?= $translator->translate('currency.symbol.placement'); ?>
                            </label>
                            <?php   $body['settings[currency_symbol_placement]'] = $s->getSetting('currency_symbol_placement'); ?>
                            <select name="settings[currency_symbol_placement]" id="settings[currency_symbol_placement]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value="before" 
                                    <?php
            $s->check_select($body['settings[currency_symbol_placement]'], 'before');
?>>
                                    <?= $translator->translate('before.amount'); ?>
                                </option>
                                <option value="after" <?php $s->check_select($body['settings[currency_symbol_placement]'], 'after'); ?>>
                                    <?= $translator->translate('after.amount'); ?>
                                </option>
                                <option value="afterspace" <?php $s->check_select($body['settings[currency_symbol_placement]'], 'afterspace'); ?>>
                                    <?= $translator->translate('after.amount.space'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[currency_code]" <?= $s->where('currency_code'); ?>>
                                <?= $translator->translate('currency.code'); ?>
                            </label>
                            <?php $body['settings[currency_code]'] = $s->getSetting('currency_code'); ?>
                            <select name="settings[currency_code]"
                                id="settings[currency_code]"
                                class="input-sm form-control">
                                <option value="0"><?= $translator->translate('none'); ?></option>
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
                                <?= $translator->translate('tax.rate.decimal.places'); ?>
                            </label>
                            <?php   $body['settings[tax_rate_decimal_places]'] = $s->getSetting('tax_rate_decimal_places'); ?>
                            <select name="settings[tax_rate_decimal_places]" id="settings[tax_rate_decimal_places]" class="form-control">
                                <option value="0"><?= $translator->translate('none'); ?></option>
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
                                <?= $translator->translate('number.format'); ?>
                            </label>
                            <?php   $body['settings[number_format]'] = $s->getSetting('number_format'); ?>                            
                            <select name="settings[number_format]" id="settings[number_format]" 
                                class="form-control">
                                <option value="0"><?= $translator->translate('none'); ?></option>
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
                <?= $translator->translate('dashboard'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[quote_overview_period]" <?= $s->where('quote_overview_period'); ?>>
                                <?= $translator->translate('quote.overview.period'); ?>
                            </label>
                            <?php $body['settings[quote_overview_period]'] = $s->getSetting('quote_overview_period'); ?>
                            <select name="settings[quote_overview_period]" id="settings[quote_overview_period]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value="0"><?= $translator->translate('none'); ?></option>
                                <option value="this-month" <?php $s->check_select($body['settings[quote_overview_period]'], 'this-month'); ?>>
                                    <?= $translator->translate('this.month'); ?>
                                </option>
                                <option value="last-month" <?php $s->check_select($body['settings[quote_overview_period]'], 'last-month'); ?>>
                                    <?= $translator->translate('last.month'); ?>
                                </option>
                                <option value="this-quarter" <?php $s->check_select($body['settings[quote_overview_period]'], 'this-quarter'); ?>>
                                    <?= $translator->translate('this.quarter'); ?>
                                </option>
                                <option value="last-quarter" <?php $s->check_select($body['settings[quote_overview_period]'], 'last-quarter'); ?>>
                                    <?= $translator->translate('last.quarter'); ?>
                                </option>
                                <option value="this-year" <?php $s->check_select($body['settings[quote_overview_period]'], 'this-year'); ?>>
                                    <?= $translator->translate('this.year'); ?>
                                </option>
                                <option value="last-year" <?php $s->check_select($body['settings[quote_overview_period]'], 'last-year'); ?>>
                                    <?= $translator->translate('last.year'); ?>
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[invoice_overview_period]" <?= $s->where('invoice_overview_period'); ?>>
                                <?= $translator->translate('overview.period'); ?>
                            </label>
                            <?php $body['settings[invoice_overview_period]'] = $s->getSetting('invoice_overview_period'); ?>
                            <select name="settings[invoice_overview_period]" id="settings[invoice_overview_period]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value="0"><?= $translator->translate('none'); ?></option>
                                <option value="this-month" <?php $s->check_select($body['settings[invoice_overview_period]'], 'this-month'); ?>>
                                    <?= $translator->translate('this.month'); ?>
                                </option>
                                <option value="last-month" <?php $s->check_select($body['settings[invoice_overview_period]'], 'last-month'); ?>>
                                    <?= $translator->translate('last.month'); ?>
                                </option>
                                <option value="this-quarter" <?php $s->check_select($body['settings[invoice_overview_period]'], 'this-quarter'); ?>>
                                    <?= $translator->translate('this.quarter'); ?>
                                </option>
                                <option value="last-quarter" <?php $s->check_select($body['settings[invoice_overview_period]'], 'last-quarter'); ?>>
                                    <?= $translator->translate('last.quarter'); ?>
                                </option>
                                <option value="this-year" <?php $s->check_select($body['settings[invoice_overview_period]'], 'this-year'); ?>>
                                    <?= $translator->translate('this.year'); ?>
                                </option>
                                <option value="last-year" <?php $s->check_select($body['settings[invoice_overview_period]'], 'last-year'); ?>>
                                    <?= $translator->translate('last.year'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="disable_quickactions" <?= $s->where('disable_quickactions'); ?>>
                                <?= $translator->translate('disable.quickactions'); ?>
                            </label>
                            <?php   $body['settings[disable_quickactions]'] = $s->getSetting('disable_quickactions'); ?>
                            <select name="settings[disable_quickactions]" class="form-control"
                                id="disable_quickactions" data-minimum-results-for-search="Infinity">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                <?php
                                    $s->check_select($body['settings[disable_quickactions]'], '1');
?>>
                                <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('interface'); ?>
            </div>
            <div class="panel-body">

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[disable_sidebar]" <?= $s->where('disable_sidebar'); ?>>
                                <?= $translator->translate('disable.sidebar'); ?>
                            </label>
                            <?php   $body['settings[disable_sidebar]'] = $s->getSetting('disable_sidebar'); ?>
                            <select name="settings[disable_sidebar]" id="settings[disable_sidebar]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
        $s->check_select($body['settings[disable_sidebar]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>                                  
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[custom_title]" <?= $s->where('custom_title'); ?>>
                                <?= $translator->translate('custom.title'); ?>
                            </label>
                            <?php $body['settings[custom_title]'] = $s->getSetting('custom_title'); ?>
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
                                <?= $translator->translate('monospaced.font.for.amounts'); ?>
                            </label>
                            <?php   $body['settings[monospace_amounts]'] = $s->getSetting('monospace_amounts'); ?>
                            <select name="settings[monospace_amounts]" class="form-control" id="monospace_amounts">
                                <option value="0"><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[monospace_amounts]'], '1'); ?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                            <p class="help-block">
                                <?= $translator->translate('example'); ?>:
                                <span style="font-family: Monaco, Lucida Console, monospace"><?= $s->format_currency(123456.78); ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[open_reports_in_new_tab]" <?= $s->where('open_reports_in_new_tab'); ?>>
                                <?= $translator->translate('open.reports.in.new.tab'); ?>
                            </label>
                            <?php  $body['settings[open_reports_in_new_tab]'] = $s->getSetting('open_reports_in_new_tab'); ?>
                            <select name="settings[open_reports_in_new_tab]" id="settings[open_reports_in_new_tab]" class="form-control">
                                <option value="0"><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[open_reports_in_new_tab]'], '1'); ?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('system.settings'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[bcc_mails_to_admin]" <?= $s->where('bcc_mails_to_admin'); ?>>
                                <?= $translator->translate('bcc.mails.to.admin'); ?>
                            </label>
                            <?php   $body['settings[bcc_mails_to_admin]'] = $s->getSetting('bcc_mails_to_admin'); ?>
                            <select name="settings[bcc_mails_to_admin]" id="settings[bcc_mails_to_admin]"
                                class="form-control">
                                <option value="0"><?= $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[bcc_mails_to_admin]'], '1'); ?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[cron_key]" <?= $s->where('cron_key'); ?>>
                                <?= $translator->translate('cron.key'); ?>
                            </label>
                            <div class="input-group">
                                <input type="text" name="settings[cron_key]" id="settings[cron_key]" class="cron_key form-control" 
                                    value="<?= (string)($body['settings[cron_key]'] ?? $s->getSetting('cron_key')); ?>">
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
