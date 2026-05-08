<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $alert
 * @var string $actionName
 * @var string $bootstrap5
 * @var string $csrf
 * @var string $frontPage
 * @var string $general
 * @var string $invoices
 * @var string $quotes
 * @var string $salesorders
 * @var string $taxes
 * @var string $email
 * @var string $online_payment
 * @var string $projects_tasks
 * @var string $google_translate
 * @var string $vat_registered
 * @var string $mpdf
 * @var string $mtd
 * @var string $oauth2
 * @var string $peppol_electronic_invoicing
 * @var string $storecove
 * @var string $invoiceplane
 * @var string $qrcode
 * @var string $active
 * @var string $telegram
 * @var string $tfa
 * @var int $fontSize
 * @var string $font
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

// array key    - active-state slug and #settings-{key} id suffix
// label        - button text
// icon         - Bootstrap icon class
// color        - CSS color for the tab accent border
// aria         - pane aria-labelledby value; empty string omits the attribute
// role         - whether to add role="tabpanel" on the pane div
// content      - injected partial string for the tab pane body
$tabs = [
 'front-page' => [
     'label' => $translator->translate('front.page'),
     'icon'  => 'bi bi-house',
     'color' => '#0d6efd',
     'aria'  => 'front-page',
     'role'  => true,
     'content' => $frontPage
 ],
 'oauth2' => [
     'label' => 'OAuth2',
     'icon'  => 'bi bi-shield-lock',
     'color' => '#0d6efd',
     'aria'  => 'oauth2',
     'role'  => true,
     'content' => $oauth2
 ],
 'general' => [
     'label' => $translator->translate('general'),
     'icon'  => 'bi bi-gear',
     'color' => '#0d6efd',
     'aria'  => '',
     'role'  => false,
     'content' => $general
 ],
 'invoices' => [
     'label' => $translator->translate('invoices'),
     'icon'  => 'bi bi-file-text',
     'color' => '#198754',
     'aria'  => 'settings-invoices',
     'role'  => true,
     'content' => $invoices
 ],
 'quotes' => [
     'label' => $translator->translate('quotes'),
     'icon'  => 'bi bi-chat-square-text',
     'color' => '#198754',
     'aria'  => 'settings-quotes',
     'role'  => true,
     'content' => $quotes
 ],
 'client-purchase-orders' => [
     'label' => $translator->translate('salesorders'),
     'icon'  => 'bi bi-cart',
     'color' => '#198754',
     'aria'  => 'settings-client-purchase-orders',
     'role'  => true,
     'content' => $salesorders
 ],
 'taxes' => [
     'label' => $translator->translate('taxes'),
     'icon'  => 'bi bi-percent',
     'color' => '#fd7e14',
     'aria'  => 'settings-taxes',
     'role'  => true,
     'content' => $taxes
 ],
 'email' => [
     'label' => $translator->translate('email'),
     'icon'  => 'bi bi-envelope',
     'color' => '#6f42c1',
     'aria'  => 'settings-email',
     'role'  => true,
     'content' => $email
 ],
 'online-payment' => [
     'label' => $translator->translate('online.payment'),
     'icon'  => 'bi bi-credit-card',
     'color' => '#6f42c1',
     'aria'  => 'settings-online-payment',
     'role'  => true,
     'content' => $online_payment
 ],
 'projects-tasks' => [
     'label' => $translator->translate('projects'),
     'icon'  => 'bi bi-kanban',
     'color' => '#6c757d',
     'aria'  => 'settings-project-tasks',
     'role'  => true,
     'content' => $projects_tasks
 ],
 'google-translate' => [
     'label' => 'Google Translate',
     'icon'  => 'bi bi-translate',
     'color' => '#6c757d',
     'aria'  => 'settings-google-translate',
     'role'  => true,
     'content' => $google_translate
 ],
 'vat-registered' => [
     'label' => $translator->translate('vat'),
     'icon'  => 'bi bi-building',
     'color' => '#fd7e14',
     'aria'  => 'settings-vat-registered',
     'role'  => true,
     'content' => $vat_registered
 ],
 'mpdf' => [
     'label' => $translator->translate('mpdf'),
     'icon'  => 'bi bi-file-pdf',
     'color' => '#6c757d',
     'aria'  => 'settings-mpdf',
     'role'  => true,
     'content' => $mpdf
 ],
 'peppol' => [
     'label' => $translator->translate('peppol.electronic.invoicing'),
     'icon'  => 'bi bi-receipt',
     'color' => '#0dcaf0',
     'aria'  => 'settings-peppol',
     'role'  => true,
     'content' => $peppol_electronic_invoicing
 ],
 'storecove' => [
     'label' => $translator->translate('storecove'),
     'icon'  => 'bi bi-cloud-upload',
     'color' => '#0dcaf0',
     'aria'  => 'settings-storecove',
     'role'  => true,
     'content' => $storecove
 ],
 'invoiceplane' => [
     'label' => $translator->translate('invoiceplane'),
     'icon'  => 'bi bi-send',
     'color' => '#0dcaf0',
     'aria'  => 'settings-invoiceplane',
     'role'  => true,
     'content' => $invoiceplane
 ],
 'qrcode' => [
     'label' => $translator->translate('qr.code'),
     'icon'  => 'bi bi-qr-code',
     'color' => '#0dcaf0',
     'aria'  => 'settings-qrcode',
     'role'  => true,
     'content' => $qrcode
 ],
 'telegram' => [
     'label' => $translator->translate('telegram'),
     'icon'  => 'bi bi-telegram',
     'color' => '#6f42c1',
     'aria'  => 'settings-telegram',
     'role'  => true,
     'content' => $telegram
 ],
 'bootstrap5' => [
     'label' => $translator->translate('bootstrap5'),
     'icon'  => 'bi bi-bootstrap',
     'color' => '#6c757d',
     'aria'  => 'settings-bootstrap5',
     'role'  => true,
     'content' => $bootstrap5
 ],
 'mtd' => [
     'label' => $translator->translate('mtd'),
     'icon'  => 'bi bi-bank',
     'color' => '#fd7e14',
     'aria'  => 'settings-mtd',
     'role'  => true,
     'content' => $mtd
 ],
 'tfa' => [
     'label' => $translator->translate('two.factor.authentication'),
     'icon'  => 'bi bi-lock',
     'color' => '#0d6efd',
     'aria'  => 'settings-tfa',
     'role'  => true,
     'content' => $tfa
 ],
];

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo H::openTag('div', [
    'style' => 'font-size: ' . ($fontSize + 2 ?: 10) . 'px; font-family: ' . $font,
]);
echo H::tag('style', '
 h1, h2, h3, h4, h5, h6,
 select, input, textarea, button, .form-control,
 .panel-heading, .panel-body, .panel-title, .panel-footer { font: inherit; }
 .panel-heading, .panel-heading h6 { font-weight: bold; }
 #settings-tabs .nav-link {
  display: flex; flex-direction: column; align-items: center; gap: 4px;
  padding: 6px 10px;
  border-top: 3px solid var(--tab-color, transparent);
  border-bottom: none;
  border-radius: 4px 4px 0 0;
  transition: background 0.15s;
 }
 #settings-tabs .nav-link i { font-size: 1.25em; color: var(--tab-color); }
 #settings-tabs .nav-link.active i { color: #fff; }
 #settings-tabs .nav-link:hover { background: rgba(0,0,0,0.05); }
 #settings-tabs .nav-link.active {
  background: var(--tab-color, #0d6efd);
  color: #fff;
  border-top-color: var(--tab-color, #0d6efd);
 }
');
echo H::openTag('div', ['id' => 'headerbar']); //0
 echo H::openTag('h1', ['class' => 'headerbar-title']); //1
  echo $translator->translate('settings');
 echo H::closeTag('h1'); //1
 echo $button::backSave();
echo H::closeTag('div'); //0

// https://getbootstrap.com/docs/5.0/components/navs-tabs/#using-data-attributes
echo H::openTag('ul', ['id' => 'settings-tabs',
    'class' => 'nav nav-tabs nav-tabs-noborder']); //0
 foreach ($tabs as $key => $tab) {
  $isActive = $active == $key;
  echo H::openTag('li', ['class' => 'nav-item'
      . ($isActive ? ' active' : ''),
      'role' => 'presentation']); //2
   echo H::openTag('button', [
    'class'          => 'nav-link' . ($isActive ? ' active' : ''),
    'data-bs-toggle' => 'tab',
    'data-bs-target' => '#settings-' . $key,
    'style'          => 'text-decoration: none; font: inherit; --tab-color: ' . $tab['color'],
   ]); //3
    echo H::openTag('i', ['class' => $tab['icon']]); //4
    echo H::closeTag('i'); //4
    echo H::openTag('span', []); //4
     echo $tab['label'];
    echo H::closeTag('span'); //4
   echo H::closeTag('button'); //3
  echo H::closeTag('li'); //2
 }
echo H::closeTag('ul'); //0

echo H::openTag('form', [
 'method'  => 'post',
 'id'      => 'form-settings',
 'action'  => $urlGenerator->generate($actionName, $actionArguments),
 'enctype' => 'multipart/form-data',
]); //0
 echo H::hiddenInput('_csrf', $csrf, ['id' => '_csrf']);
 echo H::openTag('div', ['class' => 'tabbable tabs-below']); //1
  echo H::openTag('div', ['class' => 'tab-content']); //2
   foreach ($tabs as $key => $tab) {
    $isActive   = $active == $key;
    $paneAttrs  = ['id' => 'settings-' . $key,
        'class' => 'tab-pane' . ($isActive ? ' active' : '')];
    if ($tab['role']) {
     $paneAttrs['role'] = 'tabpanel';
    }
    if ($tab['aria'] !== '') {
     $paneAttrs['aria-labelledby'] = $tab['aria'];
    }
    echo H::openTag('div', $paneAttrs); //4
     echo $tab['content'];
    echo H::closeTag('div'); //4
   }
  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('form'); //0
echo H::closeTag('div');
