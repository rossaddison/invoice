<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
*/

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-8 col-md-offset-2']); //2
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo H::openTag('h6');
     echo $translator->translate('mpdf');
    echo H::closeTag('h6');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[mpdf_ltr]'
       ]);
        echo $translator->translate('mpdf.ltr');
       echo H::closeTag('label');
       $body['settings[mpdf_ltr]'] =
       $s->getSetting('mpdf_ltr');
       echo H::openTag('select', [
        'name' => 'settings[mpdf_ltr]',
        'id' => 'settings[mpdf_ltr]',
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body['settings[mpdf_ltr]'] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body['settings[mpdf_ltr]'] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[mpdf_cjk]'
       ]);
        echo $translator->translate('mpdf.cjk');
       echo H::closeTag('label');
       $body['settings[mpdf_cjk]'] =
       $s->getSetting('mpdf_cjk');
       echo H::openTag('select', [
        'name' => 'settings[mpdf_cjk]',
        'id' => 'settings[mpdf_cjk]',
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body['settings[mpdf_cjk]'] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body['settings[mpdf_cjk]'] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[mpdf_auto_script_to_lang]'
       ]);
        echo $translator->translate('mpdf.auto.script.to.lang');
       echo H::closeTag('label');
       $body['settings[mpdf_auto_script_to_lang]'] =
       $s->getSetting('mpdf_auto_script_to_lang');
       echo H::openTag('select', [
        'name' => 'settings[mpdf_auto_script_to_lang]',
        'id' => 'settings[mpdf_auto_script_to_lang]',
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected(
          $body['settings[mpdf_auto_script_to_lang]'] == '0'
         )
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[mpdf_auto_script_to_lang]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[mpdf_auto_vietnamese]'
       ]);
        echo $translator->translate('mpdf.auto.vietnamese');
       echo H::closeTag('label');
       $body['settings[mpdf_auto_vietnamese]'] =
       $s->getSetting('mpdf_auto_vietnamese');
       echo H::openTag('select', [
        'name' => 'settings[mpdf_auto_vietnamese]',
        'id' => 'settings[mpdf_auto_vietnamese]',
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected(
          $body['settings[mpdf_auto_vietnamese]'] == '0'
         )
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[mpdf_auto_vietnamese]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[mpdf_allow_charset_conversion]'
       ]);
        echo $translator->translate(
         'mpdf.allow.charset.conversion'
        );
       echo H::closeTag('label');
       $body['settings[mpdf_allow_charset_conversion]'] =
       $s->getSetting('mpdf_allow_charset_conversion');
       echo H::openTag('select', [
        'name' => 'settings[mpdf_allow_charset_conversion]',
        'id' => 'settings[mpdf_allow_charset_conversion]',
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected(
          $body['settings[mpdf_allow_charset_conversion]'] == '0'
         )
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[mpdf_allow_charset_conversion]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[mpdf_auto_arabic]'
       ]);
        echo $translator->translate('mpdf.auto.arabic');
       echo H::closeTag('label');
       $body['settings[mpdf_auto_arabic]'] =
       $s->getSetting('mpdf_auto_arabic');
       echo H::openTag('select', [
        'name' => 'settings[mpdf_auto_arabic]',
        'id' => 'settings[mpdf_auto_arabic]',
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body['settings[mpdf_auto_arabic]'] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body['settings[mpdf_auto_arabic]'] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[mpdf_auto_language_to_font]'
       ]);
        echo $translator->translate('mpdf.auto.language.to.font');
       echo H::closeTag('label');
       $body['settings[mpdf_auto_language_to_font]'] =
       $s->getSetting('mpdf_auto_language_to_font');
       echo H::openTag('select', [
        'name' => 'settings[mpdf_auto_language_to_font]',
        'id' => 'settings[mpdf_auto_language_to_font]',
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected(
          $body['settings[mpdf_auto_language_to_font]'] == '0'
         )
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[mpdf_auto_language_to_font]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[mpdf_show_image_errors]'
       ]);
        echo $translator->translate('mpdf.show.image.errors');
       echo H::closeTag('label');
       $body['settings[mpdf_show_image_errors]'] =
       $s->getSetting('mpdf_show_image_errors');
       echo H::openTag('select', [
        'name' => 'settings[mpdf_show_image_errors]',
        'id' => 'settings[mpdf_show_image_errors]',
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected(
          $body['settings[mpdf_show_image_errors]'] == '0'
         )
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[mpdf_show_image_errors]'] == '1'
         )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
