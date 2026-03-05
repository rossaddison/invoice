<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
*/

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-xs-12 col-md-6'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$checkbox = ['class' => 'checkbox'];

echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('vat');
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('div', $checkbox); //8
        $body['settings[enable_vat_registration]'] =
        $s->getSetting('enable_vat_registration');
        echo H::openTag('label');
         echo H::openTag('input', [
          'type' => 'hidden',
          'name' => 'settings[enable_vat_registration]',
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'name' => 'settings[enable_vat_registration]',
          'value' => '1',
          'checked' => ($body['settings[enable_vat_registration]'] == 1)
          ? 'checked' : null
         ]);
         echo $translator->translate('enable.vat');
        echo H::closeTag('label');
       echo H::closeTag('div'); //8
       echo H::openTag('div', $checkbox); //8
        $body['settings[display_vat_enabled_message]'] =
        $s->getSetting('display_vat_enabled_message');
        echo H::openTag('label');
         echo H::openTag('input', [
          'type' => 'hidden',
          'name' => 'settings[display_vat_enabled_message]',
          'id' => 'dvem_hidden',
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'name' => 'settings[display_vat_enabled_message]',
          'id' => 'dvem_checkbox',
          'value' => '1',
          'checked' => ($body['settings[display_vat_enabled_message]'] == 1)
          ? 'checked' : null
         ]);
         echo $translator->translate('enable.vat.message');
        echo H::closeTag('label');
        echo H::openTag('br');
        echo H::openTag('br');
        echo H::openTag('p');
         echo $translator->translate('enable.vat.warning.line.1');
        echo H::closeTag('p');
        echo H::openTag('p');
         echo $translator->translate('enable.vat.warning.line.2');
        echo H::closeTag('p');
        echo H::openTag('p');
         echo $translator->translate('enable.vat.warning.line.3');
        echo H::closeTag('p');
        echo H::openTag('p');
         echo $translator->translate('enable.vat.warning.line.4');
        echo H::closeTag('p');
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
