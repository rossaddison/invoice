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
      $body['settings[fph_connection_method]'] =
      $s->getSetting('fph_connection_method');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_connection_method]',
       'id' => 'settings[fph_connection_method]',
       'class' => $formControl,
       'readonly' => true,
       'value' => strlen($body['settings[fph_connection_method]']) > 0
       ? $body['settings[fph_connection_method]']
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
      $body['settings[fph_client_browser_js_user_agent]'] =
      $s->getSetting('fph_client_browser_js_user_agent');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_client_browser_js_user_agent]',
       'id' => 'settings[fph_client_browser_js_user_agent]',
       'class' => $formControl,
       'readonly' => true,
       'value' => $body['settings[fph_client_browser_js_user_agent]'],
      ]);
      echo H::openTag('label');
       echo H::openTag('h4');
        echo $translator->translate('mtd.gov.client.device.id')
        . ' '
        . $translator->translate('mtd.gov.client.device.id.eg');
       echo H::closeTag('h4');
      echo H::closeTag('label');
      // Client Device Id
      $body['settings[fph_client_device_id]'] =
      $s->getSetting('fph_client_device_id');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_client_device_id]',
       'id' => 'settings[fph_client_device_id]',
       'class' => $formControl,
       'readonly' => true,
       'value' => $body['settings[fph_client_device_id]'],
      ]);
      echo H::openTag('label');
       echo H::openTag('h4');
        echo $translator->translate('mtd.gov.client.screens');
       echo H::closeTag('h4');
      echo H::closeTag('label');
      echo H::openTag('br');
      // Screen Width
      echo H::openTag('label', ['for' => 'settings[fph_screen_width]']);
       echo $translator->translate('mtd.gov.client.screens.width')
       . ' ('
       . $translator->translate('mtd.gov.client.screens.pixels')
       . ')';
      echo H::closeTag('label');
      $body['settings[fph_screen_width]'] = $s->getSetting('fph_screen_width');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_screen_width]',
       'id' => 'settings[fph_screen_width]',
       'class' => $formControl,
       'readonly' => true,
       'value' => $body['settings[fph_screen_width]'],
      ]);
      // Screen Height
      echo H::openTag('label', ['for' => 'settings[fph_screen_height]']);
       echo $translator->translate('mtd.gov.client.screens.height')
       . ' ('
       . $translator->translate('mtd.gov.client.screens.pixels')
       . ')';
      echo H::closeTag('label');
      $body['settings[fph_screen_height]'] = $s->getSetting('fph_screen_height');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_screen_height]',
       'id' => 'settings[fph_screen_height]',
       'class' => $formControl,
       'readonly' => true,
       'value' => $body['settings[fph_screen_height]'],
      ]);
      // Screen Scaling Factor
      echo H::openTag('label', ['for' => 'settings[fph_screen_scaling_factor]']);
       echo $translator->translate('mtd.gov.client.screens.scaling.factor')
       . ' ('
       . $translator->translate('mtd.gov.client.screens.scaling.factor.bits')
       . ')';
      echo H::closeTag('label');
      $body['settings[fph_screen_scaling_factor]'] =
      $s->getSetting('fph_screen_scaling_factor');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_screen_scaling_factor]',
       'id' => 'settings[fph_screen_scaling_factor]',
       'class' => $formControl,
       'readonly' => true,
       'value' => $body['settings[fph_screen_scaling_factor]'],
      ]);
      // Screen Colour Depth
      echo H::openTag('label', ['for' => 'settings[fph_screen_colour_depth]']);
       echo $translator->translate('mtd.gov.client.screens.colour.depth');
      echo H::closeTag('label');
      $body['settings[fph_screen_colour_depth]'] =
      $s->getSetting('fph_screen_colour_depth');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_screen_colour_depth]',
       'id' => 'settings[fph_screen_colour_depth]',
       'class' => $formControl,
       'readonly' => true,
       'value' => $body['settings[fph_screen_colour_depth]'],
      ]);
      // Timestamp
      echo H::openTag('label', ['for' => 'settings[fph_timestamp]']);
       echo $translator->translate('mtd.fph.screen.timestamp');
      echo H::closeTag('label');
      $body['settings[fph_timestamp]'] = $s->getSetting('fph_timestamp');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_timestamp]',
       'id' => 'settings[fph_timestamp]',
       'class' => $formControl,
       'readonly' => true,
       'value' => $body['settings[fph_timestamp]'],
      ]);
      // Client Window Size
      echo H::openTag('label', ['for' => 'settings[fph_window_size]']);
       echo H::openTag('h4');
        echo $translator->translate('mtd.gov.client.window.size')
        . ' ('
        . $translator->translate('mtd.gov.client.window.size.pixels')
        . ')';
       echo H::closeTag('h4');
      echo H::closeTag('label');
      $body['settings[fph_window_size]'] = $s->getSetting('fph_window_size');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_window_size]',
       'id' => 'settings[fph_window_size]',
       'class' => $formControl,
       'readonly' => true,
       'value' => $body['settings[fph_window_size]'],
      ]);
      // Client User Id
      echo H::openTag('label', ['for' => 'settings[fph_gov_client_user_id]']);
       echo H::openTag('h4');
        echo $translator->translate('mtd.gov.client.user.ids')
        . ' ('
        . $translator->translate('mtd.gov.client.user.ids.uuid')
        . ')';
       echo H::closeTag('h4');
      echo H::closeTag('label');
      $body['settings[fph_gov_client_user_id]'] =
      $s->getSetting('fph_gov_client_user_id');
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[fph_gov_client_user_id]',
       'id' => 'settings[fph_gov_client_user_id]',
       'class' => $formControl,
       'readonly' => true,
       'value' => $body['settings[fph_gov_client_user_id]'],
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
