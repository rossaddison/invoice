<?php
declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Group\GroupRepository $gR
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
*/

$settingKey = 'settings[default_client_purchase_order_group]';

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-8 col-md-offset-2']); //2
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo $translator->translate('salesorders');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'row']); //5
     echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //6
      echo H::openTag('div', ['class' => 'form-group']); //7
       echo H::openTag('label', [
        'for' => $settingKey
       ]);
        echo $translator->translate('salesorder.default.group');
       echo H::closeTag('label');
       $body[$settingKey] =
       $s->getSetting('default_client_purchase_order_group');
       echo H::openTag('select', [
        'name' => $settingKey,
        'id' => $settingKey,
        'class' => 'form-control form-control-lg',
       ]);
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'));
        /**
        * @var App\Invoice\Entity\Group $invoice_group
        */
        foreach ($gR->findAllPreloaded() as $invoice_group) {
        $selected = $body[
         'settings[default_client_purchase_order_group]'
        ] == $invoice_group->getId();
        echo  new Option()
         ->value($invoice_group->getId())
         ->selected($selected)
         ->content($invoice_group->getName() ?? '');
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
