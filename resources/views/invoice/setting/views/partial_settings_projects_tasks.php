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
 echo H::openTag('div', [ //2
  'class' => 'col-xs-12 col-md-8 col-md-offset-2'
 ]);
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('projects');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', ['for' => 'settings[projects_enabled]']);
        echo $translator->translate('enable.projects');
       echo H::closeTag('label');
       $body['settings[projects_enabled]'] =
       $s->getSetting('projects_enabled');
       echo H::openTag('select', [
        'name' => 'settings[projects_enabled]',
        'class' => 'form-control',
        'id' => 'settings[projects_enabled]'
       ]);
        $options = [
         '0' => $translator->translate('no'),
         '1' => $translator->translate('yes')
        ];
        /**
        * @var string $value
        * @var string $label
        */
        foreach ($options as $value => $label) {
        echo Option::tag()
         ->value($value)
         ->selected(
          $value == ($body['settings[projects_enabled]'] ?? '0')
         )
         ->content(H::encode($label));
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', ['for' => 'settings[default_hourly_rate]']);
        echo $translator->translate('default.hourly.rate');
       echo H::closeTag('label');
       $body['settings[default_hourly_rate]'] =
       $s->getSetting('default_hourly_rate');
       $formatted_rate = $body['settings[default_hourly_rate]']
       ? $s->format_amount((float) $body['settings[default_hourly_rate]'])
       : $body['settings[default_hourly_rate]'];
       echo H::openTag('div', ['class' => 'input-group']); //8
        echo H::input('text', 'settings[default_hourly_rate]',
         $formatted_rate, [
         'id' => 'settings[default_hourly_rate]',
         'class' => 'form-control amount'
        ]);
        echo H::openTag('span', ['class' => 'input-group-addon']);
         echo $s->getSetting('currency_symbol');
        echo H::closeTag('span');
        echo H::input('hidden',
         'settings[default_hourly_rate_field_is_amount]', '1');
         echo H::closeTag('div'); //10
         echo H::closeTag('div'); //10
         echo H::closeTag('div'); //10
         echo H::closeTag('div'); //10
         echo H::closeTag('div'); //10
         echo H::closeTag('div'); //10
         echo H::closeTag('div'); //10
         echo H::closeTag('div'); //10
