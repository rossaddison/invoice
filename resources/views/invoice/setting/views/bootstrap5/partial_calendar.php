<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */

$variants = [
    'primary' => 'primary',
    'secondary' => 'secondary',
    'success' => 'success',
    'danger' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
    'dark' => 'dark',
];

echo H::openTag('div', ['class' => 'border border-line-1 border-success']); //1
 echo H::openTag('div', ['class' => 'col-12 col-md-6']); //2
  echo H::openTag('div', ['class' => 'mb-3']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_calendar_accent_color]']);
    echo $translator->translate('bootstrap5.calendar.accent.color');
   echo H::closeTag('label');
   echo H::openTag('select', [
    'name' => 'settings[bootstrap5_calendar_accent_color]',
    'id' => 'settings[bootstrap5_calendar_accent_color]',
    'class' => 'form-select',
   ]);
   /**
    * @var string $translationKey
    * @var string $value
    */
    foreach ($variants as $translationKey => $value) {
     echo  new Option()
      ->value($value)
      ->selected($body['settings[bootstrap5_calendar_accent_color]'] === $value)
      ->content($translator->translate($translationKey));
    }
   echo H::closeTag('select');
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
