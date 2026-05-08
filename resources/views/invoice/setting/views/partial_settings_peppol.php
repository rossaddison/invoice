<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;
use Yiisoft\Html\Tag\I;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var array $stand_in_codes
* @var array $gateway_currency_codes
* @var string $config_tax_currency
*/

$kEnablePeppol = 'settings[enable_peppol]';
$kPeppolDebugEmojis = 'settings[peppol_debug_with_emojis]';
$kPeppolDebugValidator = 'settings[peppol_debug_with_internal_validator]';
$kPeppolXmlStream = 'settings[peppol_xml_stream]';
$kPeppolDocCurrency = 'settings[peppol_document_currency]';
$kCurrencyCodeFrom = 'settings[currency_code_from]';
$kCurrencyCodeTo = 'settings[currency_code_to]';
$kCurrencyFromTo = 'settings[currency_from_to]';
$kCurrencyToFrom = 'settings[currency_to_from]';
$kStandInCode = 'settings[stand_in_code]';
$kEnableClientPeppol = 'settings[enable_client_peppol_defaults]';
$kIncludeDelivery = 'settings[include_delivery_period]';
$inputSmFormControl = 'input-sm form-control';

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-xs-12 col-md-6'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$checkbox = ['class' => 'checkbox'];
$inputGroup = ['class' => 'input-group'];
$inputGroupText = ['class' => 'input-group-text'];
$iP = 'https://docs.peppol.eu/poacc/billing/3.0/'
        . 'syntax/ubl-invoice/cac-InvoicePeriod/';
echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2

  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate('peppol.electronic.invoicing');
   echo H::closeTag('div'); //4
   /* Enable Peppol */
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('div', $checkbox); //8
        $body[$kEnablePeppol] =
        $s->getSetting('enable_peppol');
        echo H::openTag('label');
         echo H::openTag('input', [
          'type' => 'hidden',
          'name' => $kEnablePeppol,
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'name' => $kEnablePeppol,
          'value' => '1',
          'checked' => ($body[
          $kEnablePeppol
         ] == '1') ? 'checked' : null
         ]);
         echo H::a(
          $translator->translate('peppol.enable'),
          'https://www.datypic.com/sc/ubl21/ss.html',
          [
          'style' => 'text-decoration:none',
          'data-bs-toggle' => 'tooltip',
          'title' => ''
         ]
         );
        echo H::closeTag('label');
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => $kPeppolDebugEmojis
      ]);
       echo $translator->translate(
        'peppol.debug.with.emojis'
       );
      echo H::closeTag('label');
      $body[$kPeppolDebugEmojis] =
      $s->getSetting(
       'peppol_debug_with_emojis'
      );
      echo H::openTag('select', [
       'name' => $kPeppolDebugEmojis,
       'id' => $kPeppolDebugEmojis,
       'class' => 'form-control form-control-lg',
      ]);
       echo  new Option()
        ->value('0')
        ->content($translator->translate('no'));
       echo  new Option()
        ->value('1')
        ->selected(
         $body[$kPeppolDebugEmojis]
         == '1'
        )
        ->content($translator->translate('yes'));
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
     /* Use Helpers\Peppol\PeppolValidator to validate the e-invoice using
     https://docs.peppol.eu/poacc/billing/3.0/2025-Q4/rules/ubl-peppol/ rules
     Defaults to Yes. Refer to */
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' =>
       $kPeppolDebugValidator
      ]);
       echo $translator->translate(
        'peppol.debug.with.internal.validator'
       );
      echo H::closeTag('label');
      $body[
       $kPeppolDebugValidator
      ] = $s->getSetting(
      'peppol_debug_with_internal_validator'
      );
      echo H::openTag('select', [
       'name' =>
       $kPeppolDebugValidator,
       'id' =>
       $kPeppolDebugValidator,
       'class' => 'form-control form-control-lg',
      ]);
       echo  new Option()
        ->value('0')
        ->content($translator->translate('no'));
       echo  new Option()
        ->value('1')
        ->selected(
         $body[
         $kPeppolDebugValidator
        ] == '1'
       )
        ->content($translator->translate('yes'));
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
     /* Fill Client Peppol Form with OpenPeppol defaults for testing */
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('div', $checkbox); //8
        $body[$kEnableClientPeppol]
        = $s->getSetting(
         'enable_client_peppol_defaults'
        );
        echo H::openTag('label');
         echo H::openTag('input', [
          'type' => 'hidden',
          'name' =>
          $kEnableClientPeppol,
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'name' =>
          $kEnableClientPeppol,
          'value' => '1',
          'checked' => ($body[
          $kEnableClientPeppol
         ] == '1') ? 'checked' : null
         ]);
         echo $translator->translate(
          'peppol.client.defaults'
         );
        echo H::closeTag('label');
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     /* Peppol From Currency e.g. GBP */
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => $kCurrencyCodeFrom
      ]);
       echo $translator->translate(
        'peppol.currency.code.from'
       );
      echo H::closeTag('label');
      $body[$kCurrencyCodeFrom] =
      $s->getSetting('currency_code_from') ?:
      $config_tax_currency;
      echo H::openTag('select', [
       'name' => $kCurrencyCodeFrom,
       'disabled' => 'disabled',
       'id' => $kCurrencyCodeFrom,
       'class' => $inputSmFormControl
      ]);
       echo  new Option()
        ->value('0')
        ->content($translator->translate('none'));
       /**
       * @var string $val
       */
       foreach (array_keys($gateway_currency_codes) as $val) {
       echo  new Option()
        ->value($val)
        ->selected(
         $body[$kCurrencyCodeFrom]
         == $val
        )
        ->content($val);
       }
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
     /* Peppol To Currency e.g. ZAR */
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => $kCurrencyCodeTo
      ]);
       echo $translator->translate(
        'peppol.currency.code.to'
       );
      echo H::closeTag('label');
      $body[$kCurrencyCodeTo] =
      $s->getSetting('currency_code_to') ?:
      $config_tax_currency;
      echo H::openTag('select', [
       'name' => $kCurrencyCodeTo,
       'id' => $kCurrencyCodeTo,
       'class' => $inputSmFormControl
      ]);
       echo  new Option()
        ->value('0')
        ->content($translator->translate('none'));
       /**
       * @var string $val
       */
       foreach (array_keys($gateway_currency_codes) as $val) {
       echo  new Option()
        ->value($val)
        ->selected(
         $body[$kCurrencyCodeTo]
         == $val
        )
        ->content($val);
       }
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
     /* Peppol Document Currency */
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => $kPeppolDocCurrency
      ]);
       echo '🛈';
       echo $translator->translate(
        'peppol.document.currency'
       );
      echo H::closeTag('label');
      $body[$kPeppolDocCurrency] = $s->getSetting('peppol_document_currency')
        ?: $config_tax_currency;
      echo H::openTag('select', [
       'name' => $kPeppolDocCurrency,
       'id' => $kPeppolDocCurrency,
       'class' => $inputSmFormControl
      ]);
       echo  new Option()
        ->value('0')
        ->content($translator->translate('none'));
       /**
       * @var string $val
       */
       foreach (array_keys($gateway_currency_codes) as $val) {
       echo  new Option()
        ->value($val)
        ->selected(
         $body[
         $kPeppolDocCurrency
        ] == $val
       )
        ->content($val);
       }
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
     /* One of 'From' Currency Today converts to this of 'To' Currency */
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => $kCurrencyFromTo
      ]);
       echo $translator->translate(
        'peppol.currency.from.to'
       );
       echo '(' . (string) H::a('xe.com', 'https://www.xe.com/') . ')';
      echo H::closeTag('label');
      $body[$kCurrencyFromTo] =
      $s->getSetting('currency_from_to') ?:
      '1.00';
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kCurrencyFromTo,
       'id' => $kCurrencyFromTo,
       'class' => 'form-control form-control-lg',
       'value' => $body[$kCurrencyFromTo]
      ]);

     echo H::closeTag('div'); //6
     /* One of 'To' Currency Today converts to this of 'From' Currency */
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => $kCurrencyToFrom
      ]);
       echo $translator->translate(
        'peppol.currency.to.from'
       );
      echo H::closeTag('label');
      $body[$kCurrencyToFrom] =
      $s->getSetting('currency_to_from') ?:
      '1.00';
      echo H::openTag('input', [
       'type' => 'text',
       'name' => $kCurrencyToFrom,
       'id' => $kCurrencyToFrom,
       'class' => 'form-control form-control-lg',
       'value' => $body[$kCurrencyToFrom]
      ]);

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('div', $checkbox); //8
        $body[
         $kIncludeDelivery
        ] = ($s->getSetting(
         'include_delivery_period'
        ) ?: '0');
        echo H::openTag('label');
         echo H::openTag('input', [
          'type' => 'hidden',
          'name' =>
          $kIncludeDelivery,
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'name' =>
          $kIncludeDelivery,
          'value' => '1',
          'checked' => ($body[
          $kIncludeDelivery
         ] == '1') ? 'checked' : null
         ]);
         echo H::a(
          $translator->translate('peppol.include.delivery.period'), $iP,
          ['style' => 'text-decoration:none']
         );
        echo H::closeTag('label');
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => $kStandInCode
      ]);
       echo H::a(
        $translator->translate('peppol.stand.in.code'), $iP,
        ['style' => 'text-decoration:none']
       );
      echo H::closeTag('label');
      echo H::openTag('div', $inputGroup); //7
       $body[$kStandInCode] =
       $s->getSetting('stand_in_code') ?: '';
       echo H::openTag('select', [
        'name' => $kStandInCode,
        'id' => $kStandInCode,
        'class' => $inputSmFormControl
       ]);
        /**
        * @var array $value
        * @var string $value['rdf:value']
        * @var string $value['rdf:comment']
        */
        foreach ($stand_in_codes as $value) {
        echo  new Option()
         ->value($value['rdf:value'])
         ->selected(
          ($body[$kStandInCode]
          ?? '') == $value['rdf:value']
         )
         ->content(
          $value['rdf:value'] . ' ' .
          (string) $value['rdfs:comment']
         );
        }
       echo H::closeTag('select');
       echo H::openTag('span', $inputGroupText);
        echo H::openTag('a', [
         'href' =>
         'https://invoice.local/w/Peppol-stand-in-code'
        ]);
         echo  new I()->class(
          'bi bi-question-circle'
         );
        echo H::closeTag('a');
       echo H::closeTag('span');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => $kPeppolXmlStream
      ]);
       echo $translator->translate(
        'peppol.xml.stream'
       );
      echo H::closeTag('label');
      $body[$kPeppolXmlStream] =
      $s->getSetting(
       'peppol_xml_stream'
      );
      echo H::openTag('select', [
       'name' => $kPeppolXmlStream,
       'id' => $kPeppolXmlStream,
       'class' => 'form-control form-control-lg',
      ]);
       echo  new Option()
        ->value('0')
        ->content($translator->translate('no'));
       echo  new Option()
        ->value('1')
        ->selected(
         $body[$kPeppolXmlStream] == '1'
        )
        ->content($translator->translate('yes'));
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
