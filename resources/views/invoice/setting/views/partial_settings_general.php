<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

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

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-xs-12 col-md-6'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$kStopLoggingIn = 'settings[stop_logging_in]';
$kStopSigningUp = 'settings[stop_signing_up]';
$kAppCdn = 'settings[app_cdn_not_node_module]';
$kInvCdn = 'settings[inv_cdn_not_node_module]';
$kInstallTest = 'settings[install_test_data]';
$kUseTest = 'settings[use_test_data]';
$kDefaultLanguage = 'settings[default_language]';
$kTimeZone = 'settings[time_zone]';
$kDefaultCountry = 'settings[default_country]';
$kDisableFlash = 'settings[disable_flash_messages]';
$kSignupAssignClient = 'settings[signup_automatically_assign_client]';
$kSignupAgeMin = 'settings[signup_default_age_minimum_eighteen]';
$kCurrencySymbol = 'settings[currency_symbol]';
$kCurrencySymbolPlacement = 'settings[currency_symbol_placement]';
$kCurrencyCode = 'settings[currency_code]';
$kTaxDecimal = 'settings[tax_rate_decimal_places]';
$kNumberFormat = 'settings[number_format]';
$kQuotePeriod = 'settings[quote_overview_period]';
$kInvoicePeriod = 'settings[invoice_overview_period]';
$kDisableSidebar = 'settings[disable_sidebar]';
$kCustomTitle = 'settings[custom_title]';
$kOpenReports = 'settings[open_reports_in_new_tab]';
$kBccMails = 'settings[bcc_mails_to_admin]';
$kCronKey = 'settings[cron_key]';

echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('general');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kStopLoggingIn
       ]);
        echo $translator->translate('stop.logging.in');
       echo H::closeTag('label');
       $body[$kStopLoggingIn] =
       $s->getSetting('stop_logging_in');
       echo H::openTag('select', [
        'name' => $kStopLoggingIn,
        'id' => $kStopLoggingIn,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$kStopLoggingIn] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kStopSigningUp
       ]);
        echo $translator->translate('stop.signing.up');
       echo H::closeTag('label');
       $body[$kStopSigningUp] =
       $s->getSetting('stop_signing_up');
       echo H::openTag('select', [
        'name' => $kStopSigningUp,
        'id' => $kStopSigningUp,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$kStopSigningUp] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kAppCdn
       ]);
        echo $translator->translate('app.cdn.not.node.module');
       echo H::closeTag('label');
       $body[$kAppCdn] =
       $s->getSetting('app_cdn_not_node_module');
       echo H::openTag('select', [
        'name' => $kAppCdn,
        'id' => $kAppCdn,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$kAppCdn] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kInvCdn
       ]);
        echo $translator->translate('inv.cdn.not.node.module');
       echo H::closeTag('label');
       $body[$kInvCdn] =
       $s->getSetting('inv_cdn_not_node_module');
       echo H::openTag('select', [
        'name' => $kInvCdn,
        'id' => $kInvCdn,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$kInvCdn] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kInstallTest
       ]);
        echo $translator->translate('test.data.install');
       echo H::closeTag('label');
       $body[$kInstallTest] =
       $s->getSetting('install_test_data');
       echo H::openTag('select', [
        'name' => $kInstallTest,
        'id' => $kInstallTest,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$kInstallTest] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kUseTest
       ]);
        echo $translator->translate('test.data.use');
       echo H::closeTag('label');
       $body[$kUseTest] =
       $s->getSetting('use_test_data');
       echo H::openTag('select', [
        'name' => $kUseTest,
        'id' => $kUseTest,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$kUseTest] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kDefaultLanguage
       ]);
        echo $translator->translate('language');
       echo H::closeTag('label');
       $body[$kDefaultLanguage] =
       $s->getSetting('default_language');
       echo H::openTag('select', [
        'name' => $kDefaultLanguage,
        'id' => $kDefaultLanguage,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('none'));
        /**
        * @var string $language
        */
        foreach ($languages as $language) {
        echo  new Option()
         ->value($language)
         ->selected(
          $body[$kDefaultLanguage] == $language
         )
         ->content(ucfirst($language));
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kTimeZone
       ]);
        echo $translator->translate('time.zone');
       echo H::closeTag('label');
       $body[$kTimeZone] = $s->getSetting('time_zone');
       echo H::openTag('select', [
        'name' => $kTimeZone,
        'id' => $kTimeZone,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('none'));
        /**
        * @var string $value
        */
        foreach ($time_zones as $value) {
        echo  new Option()
         ->value($value)
         ->selected($body[$kTimeZone] == $value)
         ->content($value);
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kDefaultCountry
       ]);
        echo $translator->translate('default.country');
       echo H::closeTag('label');
       $body[$kDefaultCountry] =
       $s->getSetting('default_country');
       echo H::openTag('select', [
        'name' => $kDefaultCountry,
        'id' => $kDefaultCountry,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('none'));
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));
        /**
        * @var string $cldr
        * @var string $country
        */
        foreach ($countries as $cldr => $country) {
        echo  new Option()
         ->value($cldr)
         ->selected(
          $body[$kDefaultCountry] == $cldr
         )
         ->content($country);
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'default_list_limit'
       ]);
        echo $translator->translate('default.list.limit');
       echo H::closeTag('label');
       $body['settings[default_list_limit]'] =
       $s->getSetting('default_list_limit');
       echo H::openTag('input', [
        'type' => 'number',
        'name' => 'settings[default_list_limit]',
        'id' => 'default_list_limit',
        'class' => 'form-control form-control-lg',
        'minlength' => '1',
        'min' => '1',
        'required' => true,
        'value' => $body['settings[default_list_limit]']
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kDisableFlash
       ]);
        echo $translator->translate('disable.flash.messages');
       echo H::closeTag('label');
       $body[$kDisableFlash] =
       $s->getSetting('disable_flash_messages');
       echo H::openTag('select', [
        'name' => $kDisableFlash,
        'id' => $kDisableFlash,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$kDisableFlash] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kSignupAssignClient
       ]);
        echo $translator->translate('assign.client.on.signup');
       echo H::closeTag('label');
       $body[$kSignupAssignClient] =
       $s->getSetting('signup_automatically_assign_client');
       echo H::openTag('select', [
        'name' => $kSignupAssignClient,
        'id' => $kSignupAssignClient,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body[
          $kSignupAssignClient
         ] == '1'
        )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' =>
        $kSignupAgeMin
       ]);
        echo $translator->translate(
         'assign.client.on.signup.default.age.minimum.eighteen'
        );
       echo H::closeTag('label');
       $body[$kSignupAgeMin] =
       $s->getSetting('signup_default_age_minimum_eighteen');
       echo H::openTag('select', [
        'name' =>
        $kSignupAgeMin,
        'id' =>
        $kSignupAgeMin,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body[
          $kSignupAgeMin
         ] == '1'
        )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('amount.settings');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kCurrencySymbol
       ]);
        echo $translator->translate('currency.symbol');
       echo H::closeTag('label');
       $body[$kCurrencySymbol] =
       $s->getSetting('currency_symbol');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => $kCurrencySymbol,
        'id' => $kCurrencySymbol,
        'class' => 'form-control form-control-lg',
        'value' => $body[$kCurrencySymbol]
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kCurrencySymbolPlacement
       ]);
        echo $translator->translate(
         'currency.symbol.placement'
        );
       echo H::closeTag('label');
       $body[$kCurrencySymbolPlacement] =
       $s->getSetting('currency_symbol_placement');
       echo H::openTag('select', [
        'name' => $kCurrencySymbolPlacement,
        'id' => $kCurrencySymbolPlacement,
        'class' => 'form-control form-control-lg',
        'data-minimum-results-for-search' => 'Infinity'
       ]);
        echo  new Option()
         ->value('before')
         ->selected(
          $body[
          $kCurrencySymbolPlacement
         ] == 'before'
        )
         ->content($translator->translate('before.amount'));
        echo  new Option()
         ->value('after')
         ->selected(
          $body[
          $kCurrencySymbolPlacement
         ] == 'after'
        )
         ->content($translator->translate('after.amount'));
        echo  new Option()
         ->value('afterspace')
         ->selected(
          $body[
          $kCurrencySymbolPlacement
         ] == 'afterspace'
        )
         ->content(
          $translator->translate('after.amount.space')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kCurrencyCode
       ]);
        echo $translator->translate('currency.code');
       echo H::closeTag('label');
       $body[$kCurrencyCode] =
       $s->getSetting('currency_code');
       echo H::openTag('select', [
        'name' => $kCurrencyCode,
        'id' => $kCurrencyCode,
        'class' => 'input-sm form-control'
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('none'));
        /**
        * @var string $key
        * @var string $val
        */
        foreach (array_keys($gateway_currency_codes) as $key) {
        echo  new Option()
         ->value($key)
         ->selected(
          $body[$kCurrencyCode] == $key
         )
         ->content($key);
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kTaxDecimal
       ]);
        echo $translator->translate(
         'tax.rate.decimal.places'
        );
       echo H::closeTag('label');
       $body[$kTaxDecimal] =
       $s->getSetting('tax_rate_decimal_places');
       echo H::openTag('select', [
        'name' => $kTaxDecimal,
        'id' => $kTaxDecimal,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('none'));
        echo  new Option()
         ->value('2')
         ->selected(
          $body[$kTaxDecimal] == '2'
         )
         ->content('2');
        echo  new Option()
         ->value('3')
         ->selected(
          $body[$kTaxDecimal] == '3'
         )
         ->content('3');
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kNumberFormat
       ]);
        echo $translator->translate('number.format');
       echo H::closeTag('label');
       $body[$kNumberFormat] =
       $s->getSetting('number_format');
       echo H::openTag('select', [
        'name' => $kNumberFormat,
        'id' => $kNumberFormat,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('none'));
        /**
        * @var string $key
        * @var array $value
        * @var string $value['label']
        */
        foreach ($number_formats as $key => $value) {
        echo  new Option()
         ->value($key)
         ->selected(
          $body[$kNumberFormat] ==
          $value['label']
         )
         ->content(
          $translator->translate($value['label'])
         );
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('dashboard');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kQuotePeriod
       ]);
        echo $translator->translate('quote.overview.period');
       echo H::closeTag('label');
       $body[$kQuotePeriod] =
       $s->getSetting('quote_overview_period');
       echo H::openTag('select', [
        'name' => $kQuotePeriod,
        'id' => $kQuotePeriod,
        'class' => 'form-control form-control-lg',
        'data-minimum-results-for-search' => 'Infinity'
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('none'));
        echo  new Option()
         ->value('this-month')
         ->selected(
          $body[
          $kQuotePeriod
         ] == 'this-month'
        )
         ->content($translator->translate('this.month'));
        echo  new Option()
         ->value('last-month')
         ->selected(
          $body[
          $kQuotePeriod
         ] == 'last-month'
        )
         ->content($translator->translate('last.month'));
        echo  new Option()
         ->value('this-quarter')
         ->selected(
          $body[
          $kQuotePeriod
         ] == 'this-quarter'
        )
         ->content($translator->translate('this.quarter'));
        echo  new Option()
         ->value('last-quarter')
         ->selected(
          $body[
          $kQuotePeriod
         ] == 'last-quarter'
        )
         ->content($translator->translate('last.quarter'));
        echo  new Option()
         ->value('this-year')
         ->selected(
          $body[
          $kQuotePeriod
         ] == 'this-year'
        )
         ->content($translator->translate('this.year'));
        echo  new Option()
         ->value('last-year')
         ->selected(
          $body[
          $kQuotePeriod
         ] == 'last-year'
        )
         ->content($translator->translate('last.year'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kInvoicePeriod
       ]);
        echo $translator->translate('overview.period');
       echo H::closeTag('label');
       $body[$kInvoicePeriod] =
       $s->getSetting('invoice_overview_period');
       echo H::openTag('select', [
        'name' => $kInvoicePeriod,
        'id' => $kInvoicePeriod,
        'class' => 'form-control form-control-lg',
        'data-minimum-results-for-search' => 'Infinity'
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('none'));
        echo  new Option()
         ->value('this-month')
         ->selected(
          $body[
          $kInvoicePeriod
         ] == 'this-month'
        )
         ->content($translator->translate('this.month'));
        echo  new Option()
         ->value('last-month')
         ->selected(
          $body[
          $kInvoicePeriod
         ] == 'last-month'
        )
         ->content($translator->translate('last.month'));
        echo  new Option()
         ->value('this-quarter')
         ->selected(
          $body[
          $kInvoicePeriod
         ] == 'this-quarter'
        )
         ->content($translator->translate('this.quarter'));
        echo  new Option()
         ->value('last-quarter')
         ->selected(
          $body[
          $kInvoicePeriod
         ] == 'last-quarter'
        )
         ->content($translator->translate('last.quarter'));
        echo  new Option()
         ->value('this-year')
         ->selected(
          $body[
          $kInvoicePeriod
         ] == 'this-year'
        )
         ->content($translator->translate('this.year'));
        echo  new Option()
         ->value('last-year')
         ->selected(
          $body[
          $kInvoicePeriod
         ] == 'last-year'
        )
         ->content($translator->translate('last.year'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'disable_quickactions'
       ]);
        echo $translator->translate('disable.quickactions');
       echo H::closeTag('label');
       $body['settings[disable_quickactions]'] =
       $s->getSetting('disable_quickactions');
       echo H::openTag('select', [
        'name' => 'settings[disable_quickactions]',
        'class' => 'form-control form-control-lg',
        'id' => 'disable_quickactions',
        'data-minimum-results-for-search' => 'Infinity'
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[disable_quickactions]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('interface');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kDisableSidebar
       ]);
        echo $translator->translate('disable.sidebar');
       echo H::closeTag('label');
       $body[$kDisableSidebar] =
       $s->getSetting('disable_sidebar');
       echo H::openTag('select', [
        'name' => $kDisableSidebar,
        'id' => $kDisableSidebar,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$kDisableSidebar] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kCustomTitle
       ]);
        echo $translator->translate('custom.title');
       echo H::closeTag('label');
       $body[$kCustomTitle] =
       $s->getSetting('custom_title');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => $kCustomTitle,
        'id' => $kCustomTitle,
        'class' => 'form-control form-control-lg',
        'value' => $body[$kCustomTitle]
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => 'monospace_amounts'
       ]);
        echo $translator->translate(
         'monospaced.font.for.amounts'
        );
       echo H::closeTag('label');
       $body['settings[monospace_amounts]'] =
       $s->getSetting('monospace_amounts');
       echo H::openTag('select', [
        'name' => 'settings[monospace_amounts]',
        'class' => 'form-control form-control-lg',
        'id' => 'monospace_amounts'
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[monospace_amounts]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
       echo H::openTag('p', ['class' => 'help-block']);
        echo $translator->translate('example') . ': ';
        echo H::openTag('span', [
         'style' => 'font-family: Monaco, Lucida Console, ' .
         'monospace'
        ]);
         echo $s->formatCurrency(123456.78);
        echo H::closeTag('span');
       echo H::closeTag('p');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kOpenReports
       ]);
        echo $translator->translate(
         'open.reports.in.new.tab'
        );
       echo H::closeTag('label');
       $body[$kOpenReports] =
       $s->getSetting('open_reports_in_new_tab');
       echo H::openTag('select', [
        'name' => $kOpenReports,
        'id' => $kOpenReports,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$kOpenReports] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('system.settings');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kBccMails
       ]);
        echo $translator->translate('bcc.mails.to.admin');
       echo H::closeTag('label');
       $body[$kBccMails] =
       $s->getSetting('bcc_mails_to_admin');
       echo H::openTag('select', [
        'name' => $kBccMails,
        'id' => $kBccMails,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$kBccMails] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', [
        'for' => $kCronKey
       ]);
        echo $translator->translate('cron.key');
       echo H::closeTag('label');
       echo H::openTag('div', ['class' => 'input-group']); //8
        echo H::openTag('input', [
         'type' => 'text',
         'name' => $kCronKey,
         'id' => $kCronKey,
         'class' => 'cron_key form-control',
         'value' => (string) ($body[$kCronKey] ??
         $s->getSetting('cron_key'))
        ]);
        echo H::openTag('div', [ //9
         'class' => 'input-group-text'
        ]);
         /**
         * Related logic: see
         * ..\src\Invoice\Asset\rebuild-1.13
         * \js\setting.js
         * Related logic: see
         * $(document).on('click',
          * '#btn_generate_cron_key', function ()
          */
          echo H::openTag('button', [
          'id' => 'btn_generate_cron_key',
          'type' => 'button',
          'class' =>
          'btn_generate_cron_key btn btn-primary btn-block'
         ]);
          echo H::openTag('i', [
           'class' => 'bi bi-recycle'
          ]);
          echo H::closeTag('i');
         echo H::closeTag('button');
        echo H::closeTag('div'); //9
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
