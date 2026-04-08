<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$guestBg = $s->getSetting('bootstrap5_sidebar_guest_background') ?: '#1a1a2e';

$items = [
 ['title' => 'clients',  'icon' => 'bi bi-people',          'color' => '#0d6efd'],
 ['title' => 'quotes',   'icon' => 'bi bi-chat-square-text', 'color' => '#198754'],
 ['title' => 'invoices', 'icon' => 'bi bi-file-text',        'color' => '#198754'],
 ['title' => 'payments', 'icon' => 'bi bi-coin',             'color' => '#fd7e14'],
];

echo H::tag('style', '
 .sidebar-guest { background: ' . $guestBg . '; }
 .sidebar-guest ul { list-style: none; padding: 0; margin: 0; }
 .sidebar-guest li a {
  display: flex; align-items: center; justify-content: center;
  padding: 12px 0;
  color: rgba(255,255,255,0.65);
  text-decoration: none;
  transition: background 0.15s, color 0.15s, border-left-color 0.15s;
  border-left: 3px solid transparent;
 }
 .sidebar-guest li a:hover {
  background: rgba(255,255,255,0.08);
  color: #fff;
  border-left-color: var(--sidebar-color);
 }
 .sidebar-guest li a i { font-size: 1.4em; }
');

echo H::openTag('div', ['class' => 'sidebar-guest hidden-xs']); //1
 echo H::openTag('ul'); //2
  foreach ($items as $item) {
   echo H::openTag('li'); //3
    echo H::openTag('a', [
     'href'              => $urlGenerator->generate('inv/guest'),
     'title'             => $translator->translate($item['title']),
     'class'             => 'tip',
     'data-bs-placement' => 'right',
     'style'             => '--sidebar-color: ' . $item['color'],
    ]); //4
     echo H::openTag('i', ['class' => $item['icon'], 'aria-hidden' => 'true']); //5
     echo H::closeTag('i'); //5
    echo H::closeTag('a'); //4
   echo H::closeTag('li'); //3
  }
 echo H::closeTag('ul'); //2
echo H::closeTag('div'); //1
