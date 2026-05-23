<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */

echo H::openTag('div', ['class' => 'border border-line-1 border-info']); //1
 echo H::openTag('div', ['class' => 'col-12 col-md-6']); //2
  echo H::openTag('div', ['class' => 'mb-3']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_sidebar_background]']);
    echo $translator->translate('bootstrap5.sidebar.background');
   echo H::closeTag('label');
   echo H::openTag('input', [
    'type'  => 'color',
    'name'  => 'settings[bootstrap5_sidebar_background]',
    'id'    => 'settings[bootstrap5_sidebar_background]',
    'value' => $body['settings[bootstrap5_sidebar_background]'] ?: '#1a1a2e',
    'class' => 'form-control',
    'style' => 'height:40px; padding:2px 4px; cursor:pointer;',
   ]);
   echo H::closeTag('input');
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
 echo H::openTag('div', ['class' => 'col-12 col-md-6']); //2
  echo H::openTag('div', ['class' => 'mb-3']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_sidebar_guest_background]']);
    echo $translator->translate('bootstrap5.sidebar.guest.background');
   echo H::closeTag('label');
   echo H::openTag('input', [
    'type'  => 'color',
    'name'  => 'settings[bootstrap5_sidebar_guest_background]',
    'id'    => 'settings[bootstrap5_sidebar_guest_background]',
    'value' => $body['settings[bootstrap5_sidebar_guest_background]'] ?: '#1a1a2e',
    'class' => 'form-control',
    'style' => 'height:40px; padding:2px 4px; cursor:pointer;',
   ]);
   echo H::closeTag('input');
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
