<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 * @var array $placements
 */

echo H::openTag('div', ['class' => 'border border-1 border-primary']); //1
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //2
  echo H::openTag('div', ['class' => 'form-group']); //3
   echo H::openTag('div', ['class' => 'checkbox']); //4
    $checked = $body['settings[bootstrap5_offcanvas_enable]'] == 1;
    echo H::openTag('label');
     echo H::openTag('input', [
      'type' => 'hidden',
      'name' => 'settings[bootstrap5_offcanvas_enable]',
      'value' => '0',
     ]);
     echo H::openTag('input', [
      'type' => 'checkbox',
      'name' => 'settings[bootstrap5_offcanvas_enable]',
      'value' => '1',
      'checked' => $checked ? 'checked' : null,
     ]);
     echo $translator->translate('bootstrap5.offcanvas.enable');
    echo H::closeTag('label');
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //2
  echo H::openTag('div', ['class' => 'form-group']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_offcanvas_placement]']);
    echo $translator->translate('bootstrap5.offcanvas.placement');
   echo H::closeTag('label');
   echo H::openTag('select', [
    'name' => 'settings[bootstrap5_offcanvas_placement]',
    'id' => 'settings[bootstrap5_offcanvas_placement]',
    'class' => 'form-control',
   ]);
    echo  new Option()
     ->value('0')
     ->content($translator->translate('none'));
   /**
    * @var string $placement
    */
    foreach ($placements as $placement) {
     echo  new Option()
      ->value($placement)
      ->selected($body['settings[bootstrap5_offcanvas_placement]'] === $placement)
      ->content(ucfirst($placement));
    }
   echo H::closeTag('select');
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
