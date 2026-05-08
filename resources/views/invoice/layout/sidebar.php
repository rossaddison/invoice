<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

// route  — route name passed to $urlGenerator->generate()
// title  — translation key
// icon   — Bootstrap Icon class
// color  — CSS accent colour for active/hover state
// show   — whether to render this item
$items = [
 ['route' => 'client/index',     'title' => 'clients',         'icon' => 'bi bi-people',          'color' => '#0d6efd', 'show' => true],
 ['route' => 'quote/index',      'title' => 'quotes',          'icon' => 'bi bi-chat-square-text', 'color' => '#198754', 'show' => true],
 ['route' => 'inv/index',        'title' => 'invoices',        'icon' => 'bi bi-file-text',        'color' => '#198754', 'show' => true],
 ['route' => 'payment/index',    'title' => 'payments',        'icon' => 'bi bi-coin',             'color' => '#fd7e14', 'show' => true],
 ['route' => 'product/index',    'title' => 'products',        'icon' => 'bi bi-box-seam',         'color' => '#6f42c1', 'show' => true],
 ['route' => 'task/index',       'title' => 'tasks',           'icon' => 'bi bi-check2-square',    'color' => '#0dcaf0', 'show' => $s->getSetting('projects_enabled') == 1],
 ['route' => 'setting/tabIndex', 'title' => 'system.settings', 'icon' => 'bi bi-gear',             'color' => '#6c757d', 'show' => true],
];

$currentName = $currentRoute->getName() ?? '';

$sidebarBg = $s->getSetting('bootstrap5_sidebar_background') ?: '#1a1a2e';
echo H::tag('style', '
 .sidebar { background: ' . $sidebarBg . '; }
 .sidebar ul { list-style: none; padding: 0; margin: 0; }
 .sidebar li a {
  display: flex; align-items: center; justify-content: center;
  padding: 12px 0;
  color: rgba(255,255,255,0.65);
  text-decoration: none;
  transition: background 0.15s, color 0.15s, border-left-color 0.15s;
  border-left: 3px solid transparent;
 }
 .sidebar li a:hover {
  background: rgba(255,255,255,0.08);
  color: #fff;
  border-left-color: var(--sidebar-color);
 }
 .sidebar li a.active {
  border-left-color: var(--sidebar-color);
  color: var(--sidebar-color);
  background: rgba(255,255,255,0.06);
 }
 .sidebar li a i { font-size: 1.4em; }
');

echo H::openTag('div', ['class' => 'sidebar hidden-xs']); //1
 echo H::openTag('ul'); //2
  foreach ($items as $item) {
   if (!$item['show']) {
    continue;
   }
   $prefix   = explode('/', $item['route'])[0];
   $isActive = str_starts_with($currentName, $prefix);
   echo H::openTag('li'); //3
    echo H::openTag('a', [
     'href'              => $urlGenerator->generate($item['route']),
     'title'             => $translator->translate($item['title']),
     'class'             => 'tip' . ($isActive ? ' active' : ''),
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
