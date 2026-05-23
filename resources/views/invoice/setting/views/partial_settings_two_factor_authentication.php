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
 echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']); //2
  echo H::openTag('div', ['class' => 'card']); //3
   echo H::openTag('div', ['class' => 'card-header']); //4
    echo $translator->translate('two.factor.authentication');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'card-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'mb-3']); //7
       echo H::openTag('div', ['class' => 'form-check']); //8
        $body['settings[enable_tfa]'] = $s->getSetting('enable_tfa');
        echo H::hiddenInput(
          'settings[enable_tfa]',
          '0'
         );
         echo H::checkbox(
          'settings[enable_tfa]',
          '1',
          [
          'class' => 'form-check-input',
          'id' => 'enable_tfa',
          'checked' => ($body['settings[enable_tfa]'] == 1)
          ? 'checked'
          : null
         ]
         );
         echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'enable_tfa']);
          echo $translator->translate(
           'two.factor.authentication.enable'
          );
         echo H::closeTag('label');
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
      echo H::openTag('div', ['class' => 'mb-3']); //7
      $tfaDisabling = 'settings[enable_tfa_with_disabling]';
       echo H::openTag(
        'label',
        ['for' => $tfaDisabling]
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
      $body[$tfaDisabling] =
      $s->getSetting('enable_tfa_with_disabling');
      echo H::openTag('select', [
       'name' => $tfaDisabling,
       'id' => $tfaDisabling,
       'class' => 'form-select',
      ]);
       echo  new Option()
        ->value('0')
        ->content($translator->translate('no'))
        ->selected(
         $body[$tfaDisabling] == '0'
        );
       echo  new Option()
        ->value('1')
        ->content($translator->translate('yes'))
        ->selected(
         $body[$tfaDisabling] == '1'
        );
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
echo H::closeTag('div'); //1
