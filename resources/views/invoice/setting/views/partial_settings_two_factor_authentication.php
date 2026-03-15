<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
*/

echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-8 col-md-offset-2']); //2
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('two.factor.authentication');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('div', ['class' => 'checkbox']); //8
        $body['settings[enable_tfa]'] = $s->getSetting('enable_tfa');
        echo H::openTag('label');
         echo H::hiddenInput(
          'settings[enable_tfa]',
          '0'
         );
         echo H::checkbox(
          'settings[enable_tfa]',
          '1',
          [
          'checked' => ($body['settings[enable_tfa]'] == 1) 
          ? 'checked' 
          : null
         ]
         );
         echo $translator->translate(
          'two.factor.authentication.enable'
         );
        echo H::closeTag('label');
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag(
        'label',
        ['for' => 'settings[enable_tfa_with_disabling]']
       );
       echo H::openTag('p');
        echo $translator->translate('yes') . ' = ';
        echo $translator->translate(
         'two.factor.authentication.enabled.with.disabling'
        );
       echo H::closeTag('p');
       echo H::openTag('p');
        echo $translator->translate('no') . ' = ';
        echo $translator->translate(
         'two.factor.authentication.enabled.without.disabling'
        );
       echo H::closeTag('p');
      echo H::closeTag('label');
      $body['settings[enable_tfa_with_disabling]'] = 
      $s->getSetting('enable_tfa_with_disabling');
      echo H::openTag('select', [
       'name' => 'settings[enable_tfa_with_disabling]',
       'id' => 'settings[enable_tfa_with_disabling]',
       'class' => 'form-control'
      ]);
       echo (new Option())
        ->value('0')
        ->content($translator->translate('no'))
        ->selected(
         $body['settings[enable_tfa_with_disabling]'] == '0'
        );
       echo (new Option())
        ->value('1')
        ->content($translator->translate('yes'))
        ->selected(
         $body['settings[enable_tfa_with_disabling]'] == '1'
        );
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
echo H::closeTag('div'); //1
