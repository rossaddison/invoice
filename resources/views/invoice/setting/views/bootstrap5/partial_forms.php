<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */

echo H::openTag('div', ['class' => 'border border-line-1 border-secondary']); //1
 echo H::openTag('div', ['class' => 'row g-3 p-2']); //2
  echo H::openTag('div', ['class' => 'col-12 col-md-4']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_form_font_size]', 'class' => 'form-label']);
    echo $translator->translate('bootstrap5.form.font.size');
    echo ' ';
    echo H::tag('i', '', [
     'class'              => 'bi bi-info-circle',
     'data-bs-toggle'     => 'popover',
     'data-bs-placement'  => 'right',
     'data-bs-title'      => $translator->translate('bootstrap5.form.font.size'),
     'data-bs-content'    => "---Step--1: Saved via the Bootstrap 5 settings tab (partial_forms.php).\r\n"
                           . "---Step--2: Defaulted to 14 in InvoiceController.php.\r\n"
                           . "---Step--3: Read in LayoutViewInjection.php → \$bootstrap5FormFontSize view variable.\r\n"
                           . "---Step--4: Emitted as --inv-form-fs CSS custom property in layout/invoice.php, applied via overrides.css.\r\n"
                           . "---Step--5: Read directly in InvoiceController::faq() for the FAQ page font size.",
     'data-popover-steps' => '',
    ]);
   echo H::closeTag('label');
   echo H::input('number', 'settings[bootstrap5_form_font_size]', (string)$body['settings[bootstrap5_form_font_size]'], [
    'id'    => 'settings[bootstrap5_form_font_size]',
    'class' => 'form-control',
    'min'   => '8',
    'max'   => '32',
    'step'  => '1',
   ]);
  echo H::closeTag('div'); //3
  echo H::openTag('div', ['class' => 'col-12 col-md-4']); //3
   echo H::openTag('label', ['for' => 'settings[bootstrap5_form_input_height]', 'class' => 'form-label']);
    echo $translator->translate('bootstrap5.form.input.height');
   echo H::closeTag('label');
   echo H::input('number', 'settings[bootstrap5_form_input_height]', (string)$body['settings[bootstrap5_form_input_height]'], [
    'id'    => 'settings[bootstrap5_form_input_height]',
    'class' => 'form-control',
    'min'   => '24',
    'max'   => '80',
    'step'  => '1',
   ]);
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
