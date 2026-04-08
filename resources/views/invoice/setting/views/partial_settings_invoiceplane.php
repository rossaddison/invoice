<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
* @var string $actionTestConnectionName
* @var string $actionImportName
* @var array $body
* @psalm-var array<string, Stringable|null|scalar> $actionTestConnectionArguments
* @psalm-var array<string, Stringable|null|scalar> $actionImportArguments
*/

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', [ //2
  'class' => 'col-xs-12 col-md-8 col-md-offset-2'
 ]);
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('invoiceplane.tables');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-8 col-md-4']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[invoiceplane_database_name]'
       ]);
        echo $translator->translate('invoiceplane.database.name');
       echo H::closeTag('label');
       $body['settings[invoiceplane_database_name]'] =
       $s->getSetting('invoiceplane_database_name');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => 'settings[invoiceplane_database_name]',
        'id' => 'settings[invoiceplane_database_name]',
        'class' => 'form-control form-control-lg',
        'value' => $body['settings[invoiceplane_database_name]']
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-8 col-md-4']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[invoiceplane_database_username]'
       ]);
        echo $translator->translate('invoiceplane.database.username');
       echo H::closeTag('label');
       $body['settings[invoiceplane_database_username]'] =
       $s->getSetting('invoiceplane_database_username');
       echo H::openTag('input', [
        'type' => 'text',
        'name' => 'settings[invoiceplane_database_username]',
        'id' => 'settings[invoiceplane_database_username]',
        'class' => 'form-control form-control-lg',
        'value' => $body['settings[invoiceplane_database_username]']
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'col-xs-8 col-md-4']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => 'settings[invoiceplane_database_password]'
       ]);
        echo $translator->translate('invoiceplane.database.password');
       echo H::closeTag('label');
       $body['settings[invoiceplane_database_password]'] =
       $s->getSetting('invoiceplane_database_password');
       echo H::openTag('input', [
        'type' => 'password',
        'name' => 'settings[invoiceplane_database_password]',
        'id' => 'settings[invoiceplane_database_password]',
        'class' => 'form-control form-control-lg',
        'value' => $body['settings[invoiceplane_database_password]']
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
     echo H::openTag('div'); //6
      echo  new A()
       ->href($urlGenerator->generate(
        $actionTestConnectionName,
        $actionTestConnectionArguments
       ))
       ->id('btn-reset')
       ->addAttributes(['type' => 'reset'])
       ->addClass('btn btn-primary me-1')
       ->content($translator->translate('invoiceplane.import'))
       ->render();
     echo H::closeTag('div'); //6
     echo H::openTag('br');
     echo H::openTag('br');
     echo H::openTag('div'); //6
      echo  new A()
       ->href($urlGenerator->generate(
        $actionImportName,
        $actionImportArguments
       ))
       ->id('btn-reset')
       ->addAttributes([
        'type' => 'submit',
        'onclick' => 'return confirm("' .
        $translator->translate(
        'invoiceplane.import.proceed.alert'
       ) . '")',
      ])
       ->addClass('btn btn-success me-1')
       ->content($translator->translate(
        'invoiceplane.import.proceed'
       ))
       ->render();
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
