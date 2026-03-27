<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

echo H::openTag('div', ['class' => 'sidebar hidden-xs']); //1
 echo H::openTag('ul'); //2
  echo H::openTag('li'); //3
   echo H::openTag('a', [
    'href'              => $urlGenerator->generate('client/index'),
    'title'             => $translator->translate('clients'),
    'class'             => 'tip',
    'data-bs-placement' => 'right'
   ]); //4
    echo H::openTag('i', ['class' => 'bi bi-people', 'aria-hidden' => 'true']); //5
    echo H::closeTag('i'); //5
   echo H::closeTag('a'); //4
  echo H::closeTag('li'); //3
  echo H::openTag('li'); //3
   echo H::openTag('a', [
    'href'              => $urlGenerator->generate('quote/index'),
    'title'             => $translator->translate('quotes'),
    'class'             => 'tip',
    'data-bs-placement' => 'right'
   ]); //4
    echo H::openTag('i', ['class' => 'fa fa-file', 'aria-hidden' => 'true']); //5
    echo H::closeTag('i'); //5
   echo H::closeTag('a'); //4
  echo H::closeTag('li'); //3
  echo H::openTag('li'); //3
   echo H::openTag('a', [
    'href'              => $urlGenerator->generate('inv/index'),
    'title'             => $translator->translate('invoices'),
    'class'             => 'tip',
    'data-bs-placement' => 'right'
   ]); //4
    echo H::openTag('i', ['class' => 'fa fa-file-text', 'aria-hidden' => 'true']); //5
    echo H::closeTag('i'); //5
   echo H::closeTag('a'); //4
  echo H::closeTag('li'); //3
  echo H::openTag('li'); //3
   echo H::openTag('a', [
    'href'              => $urlGenerator->generate('payment/index'),
    'title'             => $translator->translate('payments'),
    'class'             => 'tip',
    'data-bs-placement' => 'right'
   ]); //4
    echo H::openTag('i', ['class' => 'bi bi-coin', 'aria-hidden' => 'true']); //5
    echo H::closeTag('i'); //5
   echo H::closeTag('a'); //4
  echo H::closeTag('li'); //3
  echo H::openTag('li'); //3
   echo H::openTag('a', [
    'href'              => $urlGenerator->generate('product/index'),
    'title'             => $translator->translate('products'),
    'class'             => 'tip',
    'data-bs-placement' => 'right'
   ]); //4
    echo H::openTag('i', ['class' => 'fa fa-database', 'aria-hidden' => 'true']); //5
    echo H::closeTag('i'); //5
   echo H::closeTag('a'); //4
  echo H::closeTag('li'); //3
  if ($s->getSetting('projects_enabled') == 1) {
   echo H::openTag('li'); //3
    echo H::openTag('a', [
     'href'              => $urlGenerator->generate('task/index'),
     'title'             => $translator->translate('tasks'),
     'class'             => 'tip',
     'data-bs-placement' => 'right'
    ]); //4
     echo H::openTag('i', ['class' => 'fa fa-check-square-o', 'aria-hidden' => 'true']); //5
     echo H::closeTag('i'); //5
    echo H::closeTag('a'); //4
   echo H::closeTag('li'); //3
  }
  echo H::openTag('li'); //3
   echo H::openTag('a', [
    'href'              => $urlGenerator->generate('setting/tabIndex'),
    'title'             => $translator->translate('system.settings'),
    'class'             => 'tip',
    'data-bs-placement' => 'right'
   ]); //4
    echo H::openTag('i', ['class' => 'fa fa-cogs', 'aria-hidden' => 'true']); //5
    echo H::closeTag('i'); //5
   echo H::closeTag('a'); //4
  echo H::closeTag('li'); //3
 echo H::closeTag('ul'); //2
echo H::closeTag('div'); //1
