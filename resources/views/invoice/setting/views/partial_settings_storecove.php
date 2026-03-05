<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var array $countries
* @var array $sender_identifier_array
* @var string $cldr
* @var string $country
*/

echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-8 col-md-offset-2']); //2
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('storecove');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[storecove_country]'
       ]);
        echo H::a(
         $translator->translate(
         'storecove.create.a.sender' .
         '.legal.entity.country'
        ),
        'https://www.storecove.com/docs/' .
        '#_create_a_sender',
        ['style' => 'text-decoration:none']
        );
       echo H::closeTag('label');
       $body['settings[storecove_country]'] = 
       $s->getSetting('storecove_country');
       echo H::openTag('select', [
        'name' => 'settings[storecove_country]',
        'id' => 'settings[storecove_country]',
        'class' => 'form-control'
       ]);
        /**
        * @var string $cldr
        * @var string $country
        */
        foreach ($countries as $cldr => $country) {
        echo Option::tag()
         ->value($cldr)
         ->selected(
          $cldr == 
          $body['settings[storecove_country]']
         )
         ->content(
          H::encode(
          $cldr . 
          str_repeat("&nbsp;", 2) . 
          str_repeat("-", 10) . 
          str_repeat("&nbsp;", 2) . 
          $country
         )
        );
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'storecove_legal_entity_id'
       ]);
        echo $translator->translate(
         'storecove.legal.entity.id.for.json'
        );
       echo H::closeTag('label');
       $body['settings[storecove_legal_entity_id]'] = 
       $s->getSetting('storecove_legal_entity_id');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => 'settings[storecove_legal_entity_id]',
        'id' => 'storecove_legal_entity_id',
        'class' => 'form-control',
        'value' => 
        $body['settings[storecove_legal_entity_id]']
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[storecove_sender_identifier]'
       ]);
        echo $translator->translate(
         'storecove.sender.identifier'
        );
       echo H::closeTag('label');
       $body['settings[storecove_sender_identifier]'] = 
       $s->getSetting('storecove_sender_identifier');
       echo H::openTag('select', [
        'name' => 
        'settings[storecove_sender_identifier]',
        'id' => 
        'settings[storecove_sender_identifier]',
        'class' => 'form-control'
       ]);
        /**
        * @var string $key
        * @var array $value
        */
        foreach ($sender_identifier_array as $key => $value) {
        /** @var string $region */
        $region = $value['Region'] ?? '';
        /** @var string $countryVal */
        $countryVal = $value['Country'] ?? '';
        /** @var string $legal */
        $legal = !empty($value['Legal']) ? 
        $value['Legal'] : 
        $translator->translate(
         'storecove.not.available'
        );
        /** @var string $tax */
        $tax = !empty($value['Tax']) ? 
        $value['Tax'] : 
        $translator->translate(
         'storecove.not.available'
        );
        /** @var string $content */
        $content = ucfirst(
         $region .
         str_repeat("&nbsp;", 2) .
         str_repeat("-", 10) .
         str_repeat("&nbsp;", 2) .
         $countryVal .
         str_repeat("&nbsp;", 2) .
         str_repeat("-", 10) .
         str_repeat("&nbsp;", 2) .
         $legal .
         str_repeat("&nbsp;", 2) .
         str_repeat("-", 10) .
         str_repeat("&nbsp;", 2) .
         $tax
        );
        echo Option::tag()
         ->value($key)
         ->selected(
          $key == 
          $body[
          'settings[storecove_sender' .
          '_identifier]'
         ]
        )
         ->content(H::encode($content));
        }
       echo H::closeTag('select');
       echo H::openTag('br');
       echo H::openTag('label', [
        'for' => 
        'storecove_sender_identifier_basis'
       ]);
        echo $translator->translate(
         'storecove.sender.identifier.basis'
        );
       echo H::closeTag('label');
       $body['settings[storecove_sender_identifier' .
        '_basis]'] = $s->getSetting(
        'storecove_sender_identifier_basis'
       );
       echo H::openTag('select', [
        'name' => 
        'settings[storecove_sender_identifier' .
        '_basis]',
        'class' => 'form-control',
        'id' => 'storecove_sender_identifier_basis',
        'data-minimum-results-for-search' => 
        'Infinity'
       ]);
        echo Option::tag()
         ->value('Legal')
         ->selected(
          'Legal' == 
          $body[
          'settings[storecove_sender' .
          '_identifier_basis]'
         ]
        )
         ->content(
          H::encode(
          $translator->translate(
          'storecove.legal'
         )
        )
        );
        echo Option::tag()
         ->value('Tax')
         ->selected(
          'Tax' == 
          $body[
          'settings[storecove_sender' .
          '_identifier_basis]'
         ]
        )
         ->content(
          H::encode(
          $translator->translate(
          'storecove.tax'
         )
        )
        );
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
