<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
 * @var App\Invoice\Setting\SettingRepository $s 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */

echo H::openTag('div', ['class' => 'border border-1 border-info']); //1
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-6']); //2
  echo H::openTag('div', ['class' => 'form-group']); //3
   echo H::openTag('label', [
    'for' => 'settings[bootstrap5_cdn_not_node_module]'
   ]);
    echo $translator->translate('bootstrap5.cdn.not.node.module');
   echo H::closeTag('label');
   $body['settings[bootstrap5_cdn_not_node_module]'] = 
   $s->getSetting('bootstrap5_cdn_not_node_module');
   echo H::openTag('select', [
    'name' => 'settings[bootstrap5_cdn_not_node_module]',
    'id' => 'settings[bootstrap5_cdn_not_node_module]',
    'class' => 'form-control'
   ]);
    echo  new Option()
     ->value('0')
     ->content($translator->translate('no'));
    echo  new Option()
     ->value('1')
     ->selected($body['settings[bootstrap5_cdn_not_node_module]'] == '1')
     ->content($translator->translate('yes'));
   echo H::closeTag('select');
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
