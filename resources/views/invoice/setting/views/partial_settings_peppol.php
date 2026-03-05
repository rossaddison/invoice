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

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-xs-12 col-md-6'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$formControl = ['class' => 'form-control'];
$checkbox = ['class' => 'checkbox'];
$inputGroup = ['class' => 'input-group'];
$inputGroupText = ['class' => 'input-group-text'];
$inputSm = ['class' => 'input-sm form-control'];
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
        $body['settings[enable_peppol]'] = 
        $s->getSetting('enable_peppol');
        echo H::openTag('label');
         echo H::openTag('input', [
          'type' => 'hidden',
          'name' => 'settings[enable_peppol]',
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'name' => 'settings[enable_peppol]',
          'value' => '1',
          'checked' => ($body[
          'settings[enable_peppol]'
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
       'for' => 'settings[peppol_debug_with_emojis]'
      ]);
       echo $translator->translate(
        'peppol.debug.with.emojis'
       );
      echo H::closeTag('label');
      $body['settings[peppol_debug_with_emojis]'] =
      $s->getSetting(
       'peppol_debug_with_emojis'
      );
      echo H::openTag('select', [
       'name' => 'settings[peppol_debug_with_emojis]',
       'id' => 'settings[peppol_debug_with_emojis]',
       'class' => 'form-control'
      ]);
       echo Option::tag()
        ->value('0')
        ->content($translator->translate('no'));
       echo Option::tag()
        ->value('1')
        ->selected(
         $body['settings[peppol_debug_with_emojis]']
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
       'settings[peppol_debug_with_internal_validator]'
      ]);
       echo $translator->translate(
        'peppol.debug.with.internal.validator'
       );
      echo H::closeTag('label');
      $body[
       'settings[peppol_debug_with_internal_validator]'
      ] = $s->getSetting(
      'peppol_debug_with_internal_validator'
      );
      echo H::openTag('select', [
       'name' => 
       'settings[peppol_debug_with_internal_validator]',
       'id' => 
       'settings[peppol_debug_with_internal_validator]',
       'class' => 'form-control'
      ]);
       echo Option::tag()
        ->value('0')
        ->content($translator->translate('no'));
       echo Option::tag()
        ->value('1')
        ->selected(
         $body[
         'settings[peppol_debug_with_internal_validator]'
        ] == '1'
       )
        ->content($translator->translate('yes'));
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
     /* Fill Client Peppol Form with OpenPeppol defaults for testing */
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('div', $checkbox); //8
        $body['settings[enable_client_peppol_defaults]']
        = $s->getSetting(
         'enable_client_peppol_defaults'
        );
        echo H::openTag('label');
         echo H::openTag('input', [
          'type' => 'hidden',
          'name' => 
          'settings[enable_client_peppol_defaults]',
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'name' => 
          'settings[enable_client_peppol_defaults]',
          'value' => '1',
          'checked' => ($body[
          'settings[enable_client_peppol_defaults]'
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
       'for' => 'settings[currency_code_from]'
      ]);
       echo $translator->translate(
        'peppol.currency.code.from'
       );
      echo H::closeTag('label');
      $body['settings[currency_code_from]'] =
      $s->getSetting('currency_code_from') ?: 
      $config_tax_currency;
      echo H::openTag('select', [
       'name' => 'settings[currency_code_from]',
       'disabled' => 'disabled',
       'id' => 'settings[currency_code_from]',
       'class' => 'input-sm form-control'
      ]);
       echo Option::tag()
        ->value('0')
        ->content($translator->translate('none'));
       /**
       * @var string $val
       * @var string $key
       */
       foreach ($gateway_currency_codes as $val => $key) {
       echo Option::tag()
        ->value($val)
        ->selected(
         $body['settings[currency_code_from]'] 
         == $val
        )
        ->content($val);
       }
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
     /* Peppol To Currency e.g. ZAR */
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => 'settings[currency_code_to]'
      ]);
       echo $translator->translate(
        'peppol.currency.code.to'
       );
      echo H::closeTag('label');
      $body['settings[currency_code_to]'] =
      $s->getSetting('currency_code_to') ?:
      $config_tax_currency;
      echo H::openTag('select', [
       'name' => 'settings[currency_code_to]',
       'id' => 'settings[currency_code_to]',
       'class' => 'input-sm form-control'
      ]);
       echo Option::tag()
        ->value('0')
        ->content($translator->translate('none'));
       /**
       * @var string $val
       * @var string $key
       */
       foreach ($gateway_currency_codes as $val => $key) {
       echo Option::tag()
        ->value($val)
        ->selected(
         $body['settings[currency_code_to]']
         == $val
        )
        ->content($val);
       }
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
     /* Peppol Document Currency */
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => 'settings[peppol_document_currency]'
      ]);
       echo '🛈';
       echo $translator->translate(
        'peppol.document.currency'
       );
      echo H::closeTag('label');
      $body['settings[peppol_document_currency]'] =
      $s->getSetting(
       'peppol_document_currency'
      ) ?: $config_tax_currency;
      echo H::openTag('select', [
       'name' => 'settings[peppol_document_currency]',
       'id' => 'settings[peppol_document_currency]',
       'class' => 'input-sm form-control'
      ]);
       echo Option::tag()
        ->value('0')
        ->content($translator->translate('none'));
       /**
       * @var string $val
       * @var string $key
       */
       foreach ($gateway_currency_codes as $val => $key) {
       echo Option::tag()
        ->value($val)
        ->selected(
         $body[
         'settings[peppol_document_currency]'
        ] == $val
       )
        ->content($val);
       }
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
     /* One of 'From' Currency Today converts to this of 'To' Currency */                    
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => 'settings[currency_from_to]'
      ]);
       echo $translator->translate(
        'peppol.currency.from.to'
       );
       echo '(' . (string) H::a('xe.com', 'https://www.xe.com/') . ')';
      echo H::closeTag('label');
      $body['settings[currency_from_to]'] =
      $s->getSetting('currency_from_to') ?: 
      '1.00';
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[currency_from_to]',
       'id' => 'settings[currency_from_to]',
       'class' => 'form-control',
       'value' => $body['settings[currency_from_to]']
      ]);

     echo H::closeTag('div'); //6
     /* One of 'To' Currency Today converts to this of 'From' Currency */
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => 'settings[currency_to_from]'
      ]);
       echo $translator->translate(
        'peppol.currency.to.from'
       );
      echo H::closeTag('label');
      $body['settings[currency_to_from]'] =
      $s->getSetting('currency_to_from') ?: 
      '1.00';
      echo H::openTag('input', [
       'type' => 'text',
       'name' => 'settings[currency_to_from]',
       'id' => 'settings[currency_to_from]',
       'class' => 'form-control',
       'value' => $body['settings[currency_to_from]']
      ]);

     echo H::closeTag('div'); //6
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('div', $checkbox); //8
        $body[
         'settings[include_delivery_period]'
        ] = ($s->getSetting(
         'include_delivery_period'
        ) ?: '0');
        echo H::openTag('label');
         echo H::openTag('input', [
          'type' => 'hidden',
          'name' => 
          'settings[include_delivery_period]',
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'name' => 
          'settings[include_delivery_period]',
          'value' => '1',
          'checked' => ($body[
          'settings[include_delivery_period]'
         ] == '1') ? 'checked' : null
         ]);
         echo H::a(
          $translator->translate('peppol.include.delivery.period'),
          'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/',
          ['style' => 'text-decoration:none']
         );
        echo H::closeTag('label');
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => 'settings[stand_in_code]'
      ]);
       echo H::a(
        $translator->translate('peppol.stand.in.code'),
        'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/'
        . 'cbc-DescriptionCode/', 
        ['style' => 'text-decoration:none']
       );
      echo H::closeTag('label');
      echo H::openTag('div', $inputGroup); //7
       $body['settings[stand_in_code]'] = 
       $s->getSetting('stand_in_code') ?: '';
       echo H::openTag('select', [
        'name' => 'settings[stand_in_code]',
        'id' => 'settings[stand_in_code]',
        'class' => 'input-sm form-control'
       ]);
        /**
        * @var array $value
        * @var string $key
        * @var string $value['rdf:value']
        * @var string $value['rdf:comment']
        */
        foreach ($stand_in_codes as $key => $value) {
        echo Option::tag()
         ->value($value['rdf:value'])
         ->selected(
          ($body['settings[stand_in_code]'] 
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
         echo I::tag()->class(
          'fa fa-question fa-fw'
         );
        echo H::closeTag('a');
       echo H::closeTag('span');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', $formGroup); //6
      echo H::openTag('label', [
       'for' => 'settings[peppol_xml_stream]'
      ]);
       echo $translator->translate(
        'peppol.xml.stream'
       );
      echo H::closeTag('label');
      $body['settings[peppol_xml_stream]'] =
      $s->getSetting(
       'peppol_xml_stream'
      );
      echo H::openTag('select', [
       'name' => 'settings[peppol_xml_stream]',
       'id' => 'settings[peppol_xml_stream]',
       'class' => 'form-control'
      ]);
       echo Option::tag()
        ->value('0')
        ->content($translator->translate('no'));
       echo Option::tag()
        ->value('1')
        ->selected(
         $body['settings[peppol_xml_stream]'] == '1'
        )
        ->content($translator->translate('yes'));
      echo H::closeTag('select');
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
