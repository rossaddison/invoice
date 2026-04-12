<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button;

/**
* Related logic: see https://github.com/MicrosoftDocs/dynamics365smb-docs/blob/main/business-central/LocalFunctionality/UnitedKingdom/fraud-prevention-data.md
* Related logic: see ...src\Invoice\Asset\rebuild\js\setting.js btn-fph-generate
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
*/

$row = ['class' => 'row'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$formControl = 'form-control form-control-lg';
$kFphConnectionMethod = 'settings[fph_connection_method]';
$kFphClientBrowserJsUserAgent = 'settings[fph_client_browser_js_user_agent]';
$kFphClientDeviceId = 'settings[fph_client_device_id]';
$kFphScreenWidth = 'settings[fph_screen_width]';
$kFphScreenHeight = 'settings[fph_screen_height]';
$kFphScreenScalingFactor = 'settings[fph_screen_scaling_factor]';
$kFphScreenColourDepth = 'settings[fph_screen_colour_depth]';
$kFphTimestamp = 'settings[fph_timestamp]';
$kFphWindowSize = 'settings[fph_window_size]';
$kFphGovClientUserId = 'settings[fph_gov_client_user_id]';

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo  new A()
     ->href('https://github.com/MicrosoftDocs/dynamics365smb-docs/blob/main/business-central/LocalFunctionality/UnitedKingdom/fraud-prevention-data.md')
     ->content($translator->translate('mtd.fph'))
     ->render();
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('br');
    echo H::openTag('label');
     echo H::openTag('h4');
      echo $translator->translate('mtd.gov.client.connection.method');
     echo H::closeTag('h4');
    echo H::closeTag('label');
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $formGroup); //6
      // Connection Method
      $body[$kFphConnectionMethod] =
      $s->getSetting('fph_connection_method');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphConnectionMethod,
       'id' => $kFphConnectionMethod,
       'class' => $formControl,
       'readonly' => true,
       'value' => strlen($body[$kFphConnectionMethod]) > 0
       ? $body[$kFphConnectionMethod]
       : 'WEB_APP_VIA_SERVER',
      ]);
      echo H::openTag('label');
       echo H::openTag('h4');
        echo $translator->translate('mtd.gov.client.browser.js.user.agent')
        . ' '
        . $translator->translate('mtd.gov.client.browser.js.user.agent.eg');
       echo H::closeTag('h4');
      echo H::closeTag('label');
      // Client Browser User Agent
      $body[$kFphClientBrowserJsUserAgent] =
      $s->getSetting('fph_client_browser_js_user_agent');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphClientBrowserJsUserAgent,
       'id' => $kFphClientBrowserJsUserAgent,
       'class' => $formControl,
       'readonly' => true,
       'value' => $body[$kFphClientBrowserJsUserAgent],
      ]);
      echo H::openTag('label');
       echo H::openTag('h4');
        echo $translator->translate('mtd.gov.client.device.id')
        . ' '
        . $translator->translate('mtd.gov.client.device.id.eg');
       echo H::closeTag('h4');
      echo H::closeTag('label');
      // Client Device Id
      $body[$kFphClientDeviceId] =
      $s->getSetting('fph_client_device_id');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphClientDeviceId,
       'id' => $kFphClientDeviceId,
       'class' => $formControl,
       'readonly' => true,
       'value' => $body[$kFphClientDeviceId],
      ]);
      echo H::openTag('label');
       echo H::openTag('h4');
        echo $translator->translate('mtd.gov.client.screens');
       echo H::closeTag('h4');
      echo H::closeTag('label');
      echo H::openTag('br');
      // Screen Width
      echo H::openTag('label', ['for' => $kFphScreenWidth]);
       echo $translator->translate('mtd.gov.client.screens.width')
       . ' ('
       . $translator->translate('mtd.gov.client.screens.pixels')
       . ')';
      echo H::closeTag('label');
      $body[$kFphScreenWidth] = $s->getSetting('fph_screen_width');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphScreenWidth,
       'id' => $kFphScreenWidth,
       'class' => $formControl,
       'readonly' => true,
       'value' => $body[$kFphScreenWidth],
      ]);
      // Screen Height
      echo H::openTag('label', ['for' => $kFphScreenHeight]);
       echo $translator->translate('mtd.gov.client.screens.height')
       . ' ('
       . $translator->translate('mtd.gov.client.screens.pixels')
       . ')';
      echo H::closeTag('label');
      $body[$kFphScreenHeight] = $s->getSetting('fph_screen_height');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphScreenHeight,
       'id' => $kFphScreenHeight,
       'class' => $formControl,
       'readonly' => true,
       'value' => $body[$kFphScreenHeight],
      ]);
      // Screen Scaling Factor
      echo H::openTag('label', ['for' => $kFphScreenScalingFactor]);
       echo $translator->translate('mtd.gov.client.screens.scaling.factor')
       . ' ('
       . $translator->translate('mtd.gov.client.screens.scaling.factor.bits')
       . ')';
      echo H::closeTag('label');
      $body[$kFphScreenScalingFactor] =
      $s->getSetting('fph_screen_scaling_factor');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphScreenScalingFactor,
       'id' => $kFphScreenScalingFactor,
       'class' => $formControl,
       'readonly' => true,
       'value' => $body[$kFphScreenScalingFactor],
      ]);
      // Screen Colour Depth
      echo H::openTag('label', ['for' => $kFphScreenColourDepth]);
       echo $translator->translate('mtd.gov.client.screens.colour.depth');
      echo H::closeTag('label');
      $body[$kFphScreenColourDepth] =
      $s->getSetting('fph_screen_colour_depth');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphScreenColourDepth,
       'id' => $kFphScreenColourDepth,
       'class' => $formControl,
       'readonly' => true,
       'value' => $body[$kFphScreenColourDepth],
      ]);
      // Timestamp
      echo H::openTag('label', ['for' => $kFphTimestamp]);
       echo $translator->translate('mtd.fph.screen.timestamp');
      echo H::closeTag('label');
      $body[$kFphTimestamp] = $s->getSetting('fph_timestamp');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphTimestamp,
       'id' => $kFphTimestamp,
       'class' => $formControl,
       'readonly' => true,
       'value' => $body[$kFphTimestamp],
      ]);
      // Client Window Size
      echo H::openTag('label', ['for' => $kFphWindowSize]);
       echo H::openTag('h4');
        echo $translator->translate('mtd.gov.client.window.size')
        . ' ('
        . $translator->translate('mtd.gov.client.window.size.pixels')
        . ')';
       echo H::closeTag('h4');
      echo H::closeTag('label');
      $body[$kFphWindowSize] = $s->getSetting('fph_window_size');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphWindowSize,
       'id' => $kFphWindowSize,
       'class' => $formControl,
       'readonly' => true,
       'value' => $body[$kFphWindowSize],
      ]);
      // Client User Id
      echo H::openTag('label', ['for' => $kFphGovClientUserId]);
       echo H::openTag('h4');
        echo $translator->translate('mtd.gov.client.user.ids')
        . ' ('
        . $translator->translate('mtd.gov.client.user.ids.uuid')
        . ')';
       echo H::closeTag('h4');
      echo H::closeTag('label');
      $body[$kFphGovClientUserId] =
      $s->getSetting('fph_gov_client_user_id');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kFphGovClientUserId,
       'id' => $kFphGovClientUserId,
       'class' => $formControl,
       'readonly' => true,
       'value' => $body[$kFphGovClientUserId],
      ]);
      echo  new Button()
       ->id('btn_fph_generate')
       ->addAttributes(['type' => 'reset', 'name' => 'btn_fph_generate'])
       ->addAttributes([
        'onclick' => 'return confirm("'
        . $translator->translate('mtd.fph.record.alert') . '")',
       ])
       ->addClass('btn btn-success me-1')
       ->content($translator->translate('mtd.fph.generate'))
       ->render();
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
