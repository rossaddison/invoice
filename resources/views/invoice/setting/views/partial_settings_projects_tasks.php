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
       $pe = 'settings[projects_enabled]';
       $body[$pe] =
       $s->getSetting('projects_enabled');
       echo H::openTag('select', [
        'name' => $pe,
        'class' => 'form-control form-control-lg',
        'id' => $pe
       ]);
        $options = [
         '0' => $translator->translate('no'),
         '1' => $translator->translate('yes')
        ];
        /**
        * @var string $value
        */
        foreach ($options as $value => $label) {
        echo  new Option()
         ->value($value)
         ->selected(
          $value == ($body[$pe] ?? '0')
         )
         ->content($label);
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       $dhr = 'settings[default_hourly_rate]';
       echo H::openTag('label', ['for' => $dhr]);
        echo $translator->translate('default.hourly.rate');
       echo H::closeTag('label');
       $body[$dhr] = $s->getSetting('default_hourly_rate');
       $formatted_rate = $body[$dhr] ? $s->formatAmount((float) $body[$dhr])
        : $body[$dhr];
       echo H::openTag('div', ['class' => 'input-group']); //8
        echo H::input('text', $dhr,
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
