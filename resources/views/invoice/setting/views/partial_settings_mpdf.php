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
       $sml = 'settings[mpdf_ltr]';
       echo H::openTag('label', ['for' => $sml]);
        echo $translator->translate('mpdf.ltr');
       echo H::closeTag('label');
       $body[$sml] = $s->getSetting('mpdf_ltr');
       echo H::openTag('select', [
        'name' => $sml,
        'id' => $sml,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$sml] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$sml] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', ['class' => 'form-group']); //7
       $smc = 'settings[mpdf_cjk]';
       echo H::openTag('label', ['for' => $smc]);
        echo $translator->translate('mpdf.cjk');
       echo H::closeTag('label');
       $body[$smc] = $s->getSetting('mpdf_cjk');
       echo H::openTag('select', [
        'name' => $smc,
        'id' => $smc,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$smc] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$smc] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', ['class' => 'form-group']); //7
       $smas = 'settings[mpdf_auto_script_to_lang]';
       echo H::openTag('label', ['for' => $smas]);
        echo $translator->translate('mpdf.auto.script.to.lang');
       echo H::closeTag('label');
       $body[$smas] = $s->getSetting('mpdf_auto_script_to_lang');
       echo H::openTag('select', [
        'name' => $smas,
        'id' => $smas,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$smas] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$smas] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', ['class' => 'form-group']); //7
       $smav = 'settings[mpdf_auto_vietnamese]';
       echo H::openTag('label', ['for' => $smav]);
        echo $translator->translate('mpdf.auto.vietnamese');
       echo H::closeTag('label');
       $body[$smav] = $s->getSetting('mpdf_auto_vietnamese');
       echo H::openTag('select', [
        'name' => $smav,
        'id' => $smav,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$smav] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$smav] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', ['class' => 'form-group']); //7
       $smac = 'settings[mpdf_allow_charset_conversion]';
       echo H::openTag('label', ['for' => $smac]);
        echo $translator->translate(
         'mpdf.allow.charset.conversion'
        );
       echo H::closeTag('label');
       $body[$smac] = $s->getSetting('mpdf_allow_charset_conversion');
       echo H::openTag('select', [
        'name' => $smac,
        'id' => $smac,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$smac] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$smac] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', ['class' => 'form-group']); //7
       $smaa = 'settings[mpdf_auto_arabic]';
       echo H::openTag('label', ['for' => $smaa]);
        echo $translator->translate('mpdf.auto.arabic');
       echo H::closeTag('label');
       $body[$smaa] = $s->getSetting('mpdf_auto_arabic');
       echo H::openTag('select', [
        'name' => $smaa,
        'id' => $smaa,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$smaa] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$smaa] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', ['class' => 'form-group']); //7
       $smalf = 'settings[mpdf_auto_language_to_font]';
       echo H::openTag('label', ['for' => $smalf]);
        echo $translator->translate('mpdf.auto.language.to.font');
       echo H::closeTag('label');
       $body[$smalf] = $s->getSetting('mpdf_auto_language_to_font');
       echo H::openTag('select', [
        'name' => $smalf,
        'id' => $smalf,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$smalf] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$smalf] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

      echo H::openTag('div', ['class' => 'form-group']); //7
       $smsie = 'settings[mpdf_show_image_errors]';
       echo H::openTag('label', ['for' => $smsie]);
        echo $translator->translate('mpdf.show.image.errors');
       echo H::closeTag('label');
       $body[$smsie] = $s->getSetting('mpdf_show_image_errors');
       echo H::openTag('select', [
        'name' => $smsie,
        'id' => $smsie,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$smsie] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$smsie] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
      echo H::closeTag('div'); //7

     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
