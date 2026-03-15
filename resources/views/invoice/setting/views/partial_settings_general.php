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

echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-8 col-md-offset-2']); //2
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('general');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[stop_logging_in]'
       ]);
        echo $translator->translate('stop.logging.in');
       echo H::closeTag('label');
       $body['settings[stop_logging_in]'] = 
       $s->getSetting('stop_logging_in');
       echo H::openTag('select', [
        'name' => 'settings[stop_logging_in]',
        'id' => 'settings[stop_logging_in]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected($body['settings[stop_logging_in]'] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[stop_signing_up]'
       ]);
        echo $translator->translate('stop.signing.up');
       echo H::closeTag('label');
       $body['settings[stop_signing_up]'] = 
       $s->getSetting('stop_signing_up');
       echo H::openTag('select', [
        'name' => 'settings[stop_signing_up]',
        'id' => 'settings[stop_signing_up]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected($body['settings[stop_signing_up]'] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[install_test_data]'
       ]);
        echo $translator->translate('test.data.install');
       echo H::closeTag('label');
       $body['settings[install_test_data]'] =
       $s->getSetting('install_test_data');
       echo H::openTag('select', [
        'name' => 'settings[install_test_data]',
        'id' => 'settings[install_test_data]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected($body['settings[install_test_data]'] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[use_test_data]'
       ]);
        echo $translator->translate('test.data.use');
       echo H::closeTag('label');
       $body['settings[use_test_data]'] =
       $s->getSetting('use_test_data');
       echo H::openTag('select', [
        'name' => 'settings[use_test_data]',
        'id' => 'settings[use_test_data]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected($body['settings[use_test_data]'] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[default_language]'
       ]);
        echo $translator->translate('language');
       echo H::closeTag('label');
       $body['settings[default_language]'] = 
       $s->getSetting('default_language');
       echo H::openTag('select', [
        'name' => 'settings[default_language]',
        'id' => 'settings[default_language]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('none'));
        /**
        * @var string $language
        */
        foreach ($languages as $language) {
        echo (new Option())
         ->value($language)
         ->selected(
          $body['settings[default_language]'] == $language
         )
         ->content(ucfirst($language));
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[time_zone]'
       ]);
        echo $translator->translate('time.zone');
       echo H::closeTag('label');
       $body['settings[time_zone]'] = $s->getSetting('time_zone');
       echo H::openTag('select', [
        'name' => 'settings[time_zone]',
        'id' => 'settings[time_zone]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('none'));
        /**
        * @var string $key
        * @var string $value
        */
        foreach ($time_zones as $key => $value) {
        echo (new Option())
         ->value($value)
         ->selected($body['settings[time_zone]'] == $value)
         ->content($value);
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[default_country]'
       ]);
        echo $translator->translate('default.country');
       echo H::closeTag('label');
       $body['settings[default_country]'] = 
       $s->getSetting('default_country');
       echo H::openTag('select', [
        'name' => 'settings[default_country]',
        'id' => 'settings[default_country]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('none'));
        echo (new Option())
         ->value('')
         ->content($translator->translate('none'));
        /**
        * @var string $cldr
        * @var string $country
        */
        foreach ($countries as $cldr => $country) {
        echo (new Option())
         ->value($cldr)
         ->selected(
          $body['settings[default_country]'] == $cldr
         )
         ->content($country);
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
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
        'class' => 'form-control',
        'minlength' => '1',
        'min' => '1',
        'required' => true,
        'value' => $body['settings[default_list_limit]']
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[disable_flash_messages]'
       ]);
        echo $translator->translate('disable.flash.messages');
       echo H::closeTag('label');
       $body['settings[disable_flash_messages]'] =
       $s->getSetting('disable_flash_messages');
       echo H::openTag('select', [
        'name' => 'settings[disable_flash_messages]',
        'id' => 'settings[disable_flash_messages]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected(
          $body['settings[disable_flash_messages]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[signup_automatically_assign_client]'
       ]);
        echo $translator->translate('assign.client.on.signup');
       echo H::closeTag('label');
       $body['settings[signup_automatically_assign_client]'] = 
       $s->getSetting('signup_automatically_assign_client');
       echo H::openTag('select', [
        'name' => 'settings[signup_automatically_assign_client]',
        'id' => 'settings[signup_automatically_assign_client]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected(
          $body[
          'settings[signup_automatically_assign_client]'
         ] == '1'
        )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 
        'settings[signup_default_age_minimum_eighteen]'
       ]);
        echo $translator->translate(
         'assign.client.on.signup.default.age.minimum.eighteen'
        );
       echo H::closeTag('label');
       $body['settings[signup_default_age_minimum_eighteen]'] = 
       $s->getSetting('signup_default_age_minimum_eighteen');
       echo H::openTag('select', [
        'name' => 
        'settings[signup_default_age_minimum_eighteen]',
        'id' => 
        'settings[signup_default_age_minimum_eighteen]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected(
          $body[
          'settings[signup_default_age_minimum_eighteen]'
         ] == '1'
        )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('amount.settings');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[currency_symbol]'
       ]);
        echo $translator->translate('currency.symbol');
       echo H::closeTag('label');
       $body['settings[currency_symbol]'] =
       $s->getSetting('currency_symbol');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => 'settings[currency_symbol]',
        'id' => 'settings[currency_symbol]',
        'class' => 'form-control',
        'value' => $body['settings[currency_symbol]']
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[currency_symbol_placement]'
       ]);
        echo $translator->translate(
         'currency.symbol.placement'
        );
       echo H::closeTag('label');
       $body['settings[currency_symbol_placement]'] = 
       $s->getSetting('currency_symbol_placement');
       echo H::openTag('select', [
        'name' => 'settings[currency_symbol_placement]',
        'id' => 'settings[currency_symbol_placement]',
        'class' => 'form-control',
        'data-minimum-results-for-search' => 'Infinity'
       ]);
        echo (new Option())
         ->value('before')
         ->selected(
          $body[
          'settings[currency_symbol_placement]'
         ] == 'before'
        )
         ->content($translator->translate('before.amount'));
        echo (new Option())
         ->value('after')
         ->selected(
          $body[
          'settings[currency_symbol_placement]'
         ] == 'after'
        )
         ->content($translator->translate('after.amount'));
        echo (new Option())
         ->value('afterspace')
         ->selected(
          $body[
          'settings[currency_symbol_placement]'
         ] == 'afterspace'
        )
         ->content(
          $translator->translate('after.amount.space')
         );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[currency_code]'
       ]);
        echo $translator->translate('currency.code');
       echo H::closeTag('label');
       $body['settings[currency_code]'] =
       $s->getSetting('currency_code');
       echo H::openTag('select', [
        'name' => 'settings[currency_code]',
        'id' => 'settings[currency_code]',
        'class' => 'input-sm form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('none'));
        /**
        * @var string $key
        * @var string $val
        */
        foreach ($gateway_currency_codes as $key => $val) {
        echo (new Option())
         ->value($key)
         ->selected(
          $body['settings[currency_code]'] == $key
         )
         ->content($key);
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[tax_rate_decimal_places]'
       ]);
        echo $translator->translate(
         'tax.rate.decimal.places'
        );
       echo H::closeTag('label');
       $body['settings[tax_rate_decimal_places]'] = 
       $s->getSetting('tax_rate_decimal_places');
       echo H::openTag('select', [
        'name' => 'settings[tax_rate_decimal_places]',
        'id' => 'settings[tax_rate_decimal_places]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('none'));
        echo (new Option())
         ->value('2')
         ->selected(
          $body['settings[tax_rate_decimal_places]'] == '2'
         )
         ->content('2');
        echo (new Option())
         ->value('3')
         ->selected(
          $body['settings[tax_rate_decimal_places]'] == '3'
         )
         ->content('3');
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[number_format]'
       ]);
        echo $translator->translate('number.format');
       echo H::closeTag('label');
       $body['settings[number_format]'] = 
       $s->getSetting('number_format');
       echo H::openTag('select', [
        'name' => 'settings[number_format]',
        'id' => 'settings[number_format]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('none'));
        /**
        * @var string $key
        * @var array $value
        * @var string $value['label']
        */
        foreach ($number_formats as $key => $value) {
        echo (new Option())
         ->value($key)
         ->selected(
          $body['settings[number_format]'] == 
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
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('dashboard');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[quote_overview_period]'
       ]);
        echo $translator->translate('quote.overview.period');
       echo H::closeTag('label');
       $body['settings[quote_overview_period]'] = 
       $s->getSetting('quote_overview_period');
       echo H::openTag('select', [
        'name' => 'settings[quote_overview_period]',
        'id' => 'settings[quote_overview_period]',
        'class' => 'form-control',
        'data-minimum-results-for-search' => 'Infinity'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('none'));
        echo (new Option())
         ->value('this-month')
         ->selected(
          $body[
          'settings[quote_overview_period]'
         ] == 'this-month'
        )
         ->content($translator->translate('this.month'));
        echo (new Option())
         ->value('last-month')
         ->selected(
          $body[
          'settings[quote_overview_period]'
         ] == 'last-month'
        )
         ->content($translator->translate('last.month'));
        echo (new Option())
         ->value('this-quarter')
         ->selected(
          $body[
          'settings[quote_overview_period]'
         ] == 'this-quarter'
        )
         ->content($translator->translate('this.quarter'));
        echo (new Option())
         ->value('last-quarter')
         ->selected(
          $body[
          'settings[quote_overview_period]'
         ] == 'last-quarter'
        )
         ->content($translator->translate('last.quarter'));
        echo (new Option())
         ->value('this-year')
         ->selected(
          $body[
          'settings[quote_overview_period]'
         ] == 'this-year'
        )
         ->content($translator->translate('this.year'));
        echo (new Option())
         ->value('last-year')
         ->selected(
          $body[
          'settings[quote_overview_period]'
         ] == 'last-year'
        )
         ->content($translator->translate('last.year'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[invoice_overview_period]'
       ]);
        echo $translator->translate('overview.period');
       echo H::closeTag('label');
       $body['settings[invoice_overview_period]'] = 
       $s->getSetting('invoice_overview_period');
       echo H::openTag('select', [
        'name' => 'settings[invoice_overview_period]',
        'id' => 'settings[invoice_overview_period]',
        'class' => 'form-control',
        'data-minimum-results-for-search' => 'Infinity'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('none'));
        echo (new Option())
         ->value('this-month')
         ->selected(
          $body[
          'settings[invoice_overview_period]'
         ] == 'this-month'
        )
         ->content($translator->translate('this.month'));
        echo (new Option())
         ->value('last-month')
         ->selected(
          $body[
          'settings[invoice_overview_period]'
         ] == 'last-month'
        )
         ->content($translator->translate('last.month'));
        echo (new Option())
         ->value('this-quarter')
         ->selected(
          $body[
          'settings[invoice_overview_period]'
         ] == 'this-quarter'
        )
         ->content($translator->translate('this.quarter'));
        echo (new Option())
         ->value('last-quarter')
         ->selected(
          $body[
          'settings[invoice_overview_period]'
         ] == 'last-quarter'
        )
         ->content($translator->translate('last.quarter'));
        echo (new Option())
         ->value('this-year')
         ->selected(
          $body[
          'settings[invoice_overview_period]'
         ] == 'this-year'
        )
         ->content($translator->translate('this.year'));
        echo (new Option())
         ->value('last-year')
         ->selected(
          $body[
          'settings[invoice_overview_period]'
         ] == 'last-year'
        )
         ->content($translator->translate('last.year'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'disable_quickactions'
       ]);
        echo $translator->translate('disable.quickactions');
       echo H::closeTag('label');
       $body['settings[disable_quickactions]'] = 
       $s->getSetting('disable_quickactions');
       echo H::openTag('select', [
        'name' => 'settings[disable_quickactions]',
        'class' => 'form-control',
        'id' => 'disable_quickactions',
        'data-minimum-results-for-search' => 'Infinity'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
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
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('interface');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[disable_sidebar]'
       ]);
        echo $translator->translate('disable.sidebar');
       echo H::closeTag('label');
       $body['settings[disable_sidebar]'] = 
       $s->getSetting('disable_sidebar');
       echo H::openTag('select', [
        'name' => 'settings[disable_sidebar]',
        'id' => 'settings[disable_sidebar]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected(
          $body['settings[disable_sidebar]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[custom_title]'
       ]);
        echo $translator->translate('custom.title');
       echo H::closeTag('label');
       $body['settings[custom_title]'] =
       $s->getSetting('custom_title');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => 'settings[custom_title]',
        'id' => 'settings[custom_title]',
        'class' => 'form-control',
        'value' => $body['settings[custom_title]']
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
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
        'class' => 'form-control',
        'id' => 'monospace_amounts'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
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
         echo $s->format_currency(123456.78);
        echo H::closeTag('span');
       echo H::closeTag('p');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[open_reports_in_new_tab]'
       ]);
        echo $translator->translate(
         'open.reports.in.new.tab'
        );
       echo H::closeTag('label');
       $body['settings[open_reports_in_new_tab]'] = 
       $s->getSetting('open_reports_in_new_tab');
       echo H::openTag('select', [
        'name' => 'settings[open_reports_in_new_tab]',
        'id' => 'settings[open_reports_in_new_tab]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected(
          $body['settings[open_reports_in_new_tab]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('system.settings');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[bcc_mails_to_admin]'
       ]);
        echo $translator->translate('bcc.mails.to.admin');
       echo H::closeTag('label');
       $body['settings[bcc_mails_to_admin]'] = 
       $s->getSetting('bcc_mails_to_admin');
       echo H::openTag('select', [
        'name' => 'settings[bcc_mails_to_admin]',
        'id' => 'settings[bcc_mails_to_admin]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected(
          $body['settings[bcc_mails_to_admin]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[cron_key]'
       ]);
        echo $translator->translate('cron.key');
       echo H::closeTag('label');
       echo H::openTag('div', ['class' => 'input-group']); //8
        echo H::openTag('input', [
         'type' => 'text',
         'name' => 'settings[cron_key]',
         'id' => 'settings[cron_key]',
         'class' => 'cron_key form-control',
         'value' => (string) ($body['settings[cron_key]'] ?? 
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
           'class' => 'fa fa-recycle fa-margin'
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
