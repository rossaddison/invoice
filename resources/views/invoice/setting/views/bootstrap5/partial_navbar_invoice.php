<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 * @var array $fonts
 * @var array $fontSizes
 */

echo H::openTag('div', ['class' => 'border border-line-1 border-danger']); //1
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //2
  echo H::openTag('div', ['class' => 'form-group']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_layout_invoice_navbar_font]']);
    echo $translator->translate('bootstrap5.layout.invoice.navbar.font');
   echo H::closeTag('label');
   echo H::openTag('select', [
    'name' => 'settings[bootstrap5_layout_invoice_navbar_font]',
    'id' => 'settings[bootstrap5_layout_invoice_navbar_font]',
    'class' => 'form-control form-control-lg',
   ]);
    echo  new Option()->value('0')->content('Arial');
   /**
    * @var string $font
    */
    foreach ($fonts as $font) {
     echo  new Option()
      ->value($font)
      ->selected($body['settings[bootstrap5_layout_invoice_navbar_font]'] === $font)
      ->content($font);
    }
   echo H::closeTag('select');
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //2
  echo H::openTag('div', ['class' => 'form-group']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_layout_invoice_navbar_font_size]']);
    echo $translator->translate('bootstrap5.layout.invoice.navbar.font.size');
   echo H::closeTag('label');
   echo H::openTag('select', [
    'name' => 'settings[bootstrap5_layout_invoice_navbar_font_size]',
    'id' => 'settings[bootstrap5_layout_invoice_navbar_font_size]',
    'class' => 'form-control form-control-lg',
   ]);
    echo  new Option()->value('0')->content('10');
   /**
    * @var string $fontSize
    */
    foreach ($fontSizes as $fontSize) {
     echo  new Option()
      ->value($fontSize)
      ->selected($body['settings[bootstrap5_layout_invoice_navbar_font_size]'] === $fontSize)
      ->content($fontSize);
    }
   echo H::closeTag('select');
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
