<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', ['class' => 'border border-line-1 border-secondary']); //1
 echo H::openTag('div', ['class' => 'row g-3 p-2']); //2
  echo H::openTag('div', ['class' => 'col-xs-12 col-md-4']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_form_font_size]', 'class' => 'form-label']);
    echo $translator->translate('bootstrap5.form.font.size');
   echo H::closeTag('label');
   echo H::input('number', 'settings[bootstrap5_form_font_size]', (string)$body['settings[bootstrap5_form_font_size]'], [
    'id'    => 'settings[bootstrap5_form_font_size]',
    'class' => 'form-control form-control-lg',
    'min'   => '8',
    'max'   => '32',
    'step'  => '1',
   ]);
  echo H::closeTag('div'); //3
  echo H::openTag('div', ['class' => 'col-xs-12 col-md-4']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_form_input_height]', 'class' => 'form-label']);
    echo $translator->translate('bootstrap5.form.input.height');
   echo H::closeTag('label');
   echo H::input('number', 'settings[bootstrap5_form_input_height]', (string)$body['settings[bootstrap5_form_input_height]'], [
    'id'    => 'settings[bootstrap5_form_input_height]',
    'class' => 'form-control form-control-lg',
    'min'   => '24',
    'max'   => '80',
    'step'  => '1',
   ]);
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
