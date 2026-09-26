<?php
declare(strict_types=1);

use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingLookupKind;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var string $tab_index_url
* @var string $quickbooks_credential_url
* @var string $quickbooks_connect_url
* @var string $bookkeeping_export_url
* @var bool $quickbooks_connected
* @var array<string, array<string, string>> $frontaccounting_lookups Keyed by
*     FrontAccountingLookupKind::value => [external_id => label], from
*     FrontAccountingLookupSyncService's cache.
* @var string $frontaccounting_lookup_sync_url
*/

$row = ['class' => 'row'];
$colMd8 = ['class' => 'col-12 col-md-8 offset-md-2'];
$panelHead = ['class' => 'card-header'];
$panelBody = ['class' => 'card-body'];
$formGroup = ['class' => 'mb-3'];
$checkbox = ['class' => 'form-check'];
$formText = ['class' => 'form-text'];

$selectedProvider = $s->getSetting('bookkeeping_provider') === 'frontaccounting' ? 'frontaccounting' : 'quickbooks';

/**
 * Each provider's fields: the last part of its Setting key
 * ('bookkeeping_{provider}_{key}'), matching that provider's gateway class
 * constants exactly (QuickBooksGateway, FrontAccountingGateway).
 */
$providers = [
    'quickbooks' => [
        'title' => 'QuickBooks',
        'fields' => [
            'client_id' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.client.id')],
            'client_secret' => ['type' => 'password', 'label' => $translator->translate('bookkeeping.quickbooks.client.secret')],
            'sandbox' => ['type' => 'checkbox', 'label' => $translator->translate('bookkeeping.quickbooks.sandbox')],
            'account_accounts_receivable' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.accounts.receivable')],
            'account_sales' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.sales')],
            'account_vat_or_tax' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.vat.or.tax')],
            'account_bank' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.bank')],
            'account_payment_fees' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.payment.fees')],
        ],
    ],
    'frontaccounting' => [
        'title' => 'FrontAccounting',
        'fields' => [
            'base_url' => [
                'type' => 'text',
                'label' => $translator->translate('bookkeeping.frontaccounting.base.url'),
                'hint' => $translator->translate('bookkeeping.frontaccounting.base.url.hint'),
            ],
            'company' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.frontaccounting.company')],
            'username' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.frontaccounting.username')],
            'password' => ['type' => 'password', 'label' => $translator->translate('bookkeeping.frontaccounting.password')],
            'bank_account' => ['type' => 'select', 'kind' => FrontAccountingLookupKind::BankAccount, 'label' => $translator->translate('bookkeeping.frontaccounting.bank.account')],
            'stock_id' => ['type' => 'select', 'kind' => FrontAccountingLookupKind::StockItem, 'label' => $translator->translate('bookkeeping.frontaccounting.stock.id')],
            'vat_stock_id' => ['type' => 'select', 'kind' => FrontAccountingLookupKind::StockItem, 'label' => $translator->translate('bookkeeping.frontaccounting.vat.stock.id')],
            'tax_group' => ['type' => 'select', 'kind' => FrontAccountingLookupKind::TaxGroup, 'label' => $translator->translate('bookkeeping.frontaccounting.tax.group')],
            'sales_type' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.frontaccounting.sales.type')],
            'payment_terms' => ['type' => 'select', 'kind' => FrontAccountingLookupKind::PaymentTerms, 'label' => $translator->translate('bookkeeping.frontaccounting.payment.terms')],
            'location' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.frontaccounting.location')],
        ],
    ],
];

echo H::openTag('div', $row); //0
 echo H::openTag('div', $colMd8); //1
  echo H::openTag('div', $formGroup); //2
   echo H::openTag('label', ['for' => 'settings[bookkeeping_provider]']); //3
    echo $translator->translate('bookkeeping.provider');
   echo H::closeTag('label'); //3
   echo H::openTag('select', [
    'name' => 'settings[bookkeeping_provider]',
    'id' => 'settings[bookkeeping_provider]',
    'class' => 'form-select',
   ]); //3
    foreach ($providers as $providerKey => $provider) {
    echo new Option()
     ->value($providerKey)
     ->selected($selectedProvider === $providerKey)
     ->content($provider['title']);
    }
   echo H::closeTag('select'); //3
   echo H::openTag('div', $formText); //3
    echo $translator->translate('bookkeeping.provider.hint');
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2

// A submit button with formaction, not a nested <form>: this partial
// already sits inside tab_index's one settings form, which also carries
// the _csrf field this POST needs.
  echo H::openTag('div', $formGroup); //2
   echo H::openTag('button', [
    'type' => 'submit',
    'formaction' => $bookkeeping_export_url,
    'formmethod' => 'post',
    'formnovalidate' => true,
    'class' => 'btn btn-success',
   ]); //3
    echo $translator->translate('bookkeeping.export.now');
   echo H::closeTag('button'); //3
   echo H::openTag('div', $formText); //3
    echo $translator->translate('bookkeeping.export.hint');
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2

  /**
   * @var string $providerKey
   * @var array{title: string, fields: array<string, array{type: string, label: string, hint?: string, kind?: FrontAccountingLookupKind}>} $provider
   */
  foreach ($providers as $providerKey => $provider) {
// NOT class "gateway-settings" -- that exact class name is queried
// unconditionally on every page load by settings.ts's
// handleOnlinePaymentSelectChange() (Online Payment's own show/hide
// mechanism, called eagerly once "to ensure initial state" regardless
// of which tab is active) and gets the "hidden" (display: none
// !important) class added to it unless it also carries
// "active-gateway" -- confirmed live as the actual root cause of this
// card silently disappearing. "bookkeeping-settings" avoids the
// collision entirely.
  $pfx = 'settings[bookkeeping_' . $providerKey . '_';
  echo H::openTag('div', [
   'id' => 'bookkeeping-settings-' . $providerKey,
   'class' => 'bookkeeping-settings card mb-3',
  ]); //2
   echo H::openTag('div', $panelHead); //3
    echo H::openTag('a', [
     'href' => $tab_index_url . '#bookkeeping-settings-' . $providerKey,
     'class' => 'text-decoration-none text-reset',
    ]); //4
     echo $provider['title'];
    echo H::closeTag('a'); //4
    if ($providerKey === 'quickbooks' && $quickbooks_credential_url !== '') {
    echo H::openTag('a', [
     'href' => $quickbooks_credential_url,
     'target' => '_blank',
     'rel' => 'noopener noreferrer',
     'class' => 'small ms-2',
    ]); //4
     echo $translator->translate('online.payment.get.credentials');
    echo H::closeTag('a'); //4
    }
    if ($providerKey === 'quickbooks') {
    echo H::openTag('span', [
     'class' => 'badge float-end ' . ($quickbooks_connected ? 'bg-success' : 'bg-secondary'),
    ]); //4
     echo $quickbooks_connected
      ? $translator->translate('bookkeeping.quickbooks.connected')
      : $translator->translate('bookkeeping.quickbooks.not.connected');
    echo H::closeTag('span'); //4
    }
    if ($selectedProvider === $providerKey) {
    echo H::openTag('span', ['class' => 'badge float-end bg-primary me-2']); //4
     echo $translator->translate('bookkeeping.provider.active');
    echo H::closeTag('span'); //4
    }
   echo H::closeTag('div'); //3

   echo H::openTag('div', $panelBody); //3
    if ($providerKey === 'quickbooks') {
    echo H::openTag('div', $formGroup); //4
     echo H::openTag('a', [
      'href' => $quickbooks_connect_url,
      'class' => 'btn btn-primary',
     ]); //5
      echo $translator->translate('bookkeeping.quickbooks.connect');
     echo H::closeTag('a'); //5
     echo H::openTag('div', $formText); //5
      echo $translator->translate('bookkeeping.quickbooks.connect.hint');
     echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4
    }

    if ($providerKey === 'frontaccounting') {
    echo H::openTag('div', $formGroup); //4
     echo H::openTag('button', [
      'type' => 'submit',
      'formaction' => $frontaccounting_lookup_sync_url,
      'formmethod' => 'post',
      'formnovalidate' => true,
      'class' => 'btn btn-outline-primary',
     ]); //5
      echo $translator->translate('bookkeeping.frontaccounting.lookup.sync.now');
     echo H::closeTag('button'); //5
     echo H::openTag('div', $formText); //5
      echo $translator->translate('bookkeeping.frontaccounting.lookup.sync.hint');
     echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4
    }

    foreach ($provider['fields'] as $key => $field) {
    $fieldId = $pfx . $key . ']';
    $body[$fieldId] = $s->getSetting('bookkeeping_' . $providerKey . '_' . $key);

    if ($field['type'] === 'checkbox') {
    echo H::openTag('div', $checkbox); //4
     echo H::openTag('input', [
      'type' => 'hidden',
      'name' => $fieldId,
      'value' => '0',
     ]); //5
     echo H::openTag('input', [
      'type' => 'checkbox',
      'class' => 'form-check-input',
      'id' => $fieldId,
      'name' => $fieldId,
      'value' => '1',
      'checked' => ($body[$fieldId] === '1') ? 'checked' : null,
     ]); //5
     echo H::openTag('label', ['class' => 'form-check-label', 'for' => $fieldId]); //5
      echo $field['label'];
     echo H::closeTag('label'); //5
    echo H::closeTag('div'); //4
    continue;
    }

    if ($field['type'] === 'select') {
    $kind = $field['kind'] ?? null;
    $options = $kind !== null ? ($frontaccounting_lookups[$kind->value] ?? []) : [];
    $currentValue = $body[$fieldId];
    echo H::openTag('div', $formGroup); //4
     echo H::openTag('label', ['for' => $fieldId]); //5
      echo $field['label'];
     echo H::closeTag('label'); //5
     if ($options === []) {
// Not synced yet (or FrontAccounting unreachable) -- fall back to a
// plain text input rather than blocking the form on an empty list.
     echo H::openTag('input', [
      'type' => 'text',
      'class' => 'form-control',
      'name' => $fieldId,
      'id' => $fieldId,
      'value' => $currentValue,
     ]); //5
     echo H::openTag('div', $formText); //5
      echo $translator->translate('bookkeeping.frontaccounting.lookup.not.synced');
     echo H::closeTag('div'); //5
     } else {
     echo H::openTag('select', [
      'name' => $fieldId,
      'id' => $fieldId,
      'class' => 'form-select',
     ]); //5
      echo new Option()->value('')->content('');
      foreach ($options as $externalId => $label) {
      echo new Option()
       ->value($externalId)
       ->selected($currentValue === $externalId)
       ->content($label . ' (' . $externalId . ')');
      }
     echo H::closeTag('select'); //5
     }
    echo H::closeTag('div'); //4
    continue;
    }

    $inputValue = $body[$fieldId];
    if ($field['type'] === 'password') {
    try {
     $inputValue = $inputValue !== '' ? (string) $s->decode($inputValue) : '';
    } catch (\App\Invoice\Libraries\CryptorException) {
     $inputValue = '';
    }
    }

    echo H::openTag('div', $formGroup); //4
     echo H::openTag('label', ['for' => $fieldId]); //5
      echo $field['label'];
     echo H::closeTag('label'); //5
     if ($field['type'] === 'password') {
     echo H::openTag('div', ['class' => 'position-relative']); //5
      echo H::openTag('input', [
       'type' => 'password',
       'class' => 'form-control',
       'style' => 'padding-right: 5rem',
       'name' => $fieldId,
       'id' => $fieldId,
       'value' => $inputValue,
      ]); //6
      echo H::openTag('button', [
       'type' => 'button',
       'class' => 'btn btn-link p-1 copy-to-clipboard-toggle',
       'style' => 'position: absolute; top: 50%; right: 2.75rem; transform: translateY(-50%);',
       'data-action' => 'copy-to-clipboard',
       'data-copy-target-id' => $fieldId,
       'data-copied-label' => $translator->translate('copied'),
       'aria-label' => $translator->translate('copy.to.clipboard'),
       'title' => $translator->translate('copy.to.clipboard'),
      ]); //6
       echo H::tag('i', '', ['class' => 'bi bi-clipboard']);
      echo H::closeTag('button'); //6
      echo H::openTag('button', [
       'type' => 'button',
       'class' => 'btn btn-link position-absolute top-50 end-0 translate-middle-y password-reveal-toggle',
       'data-target' => $fieldId,
       'aria-label' => 'Show password',
      ]); //6
       echo H::tag('i', '', ['class' => 'bi bi-eye']);
      echo H::closeTag('button'); //6
     echo H::closeTag('div'); //5
     echo H::openTag('input', [
      'type' => 'hidden',
      'value' => '1',
      'name' => $pfx . $key . '_field_is_password]',
     ]); //5
     } else {
     echo H::openTag('input', [
      'type' => 'text',
      'class' => 'form-control',
      'name' => $fieldId,
      'id' => $fieldId,
      'value' => $inputValue,
     ]); //5
     }
     if (isset($field['hint'])) {
     echo H::openTag('div', $formText); //5
      echo $field['hint'];
     echo H::closeTag('div'); //5
     }
    echo H::closeTag('div'); //4
    }

   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2
  }

 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
