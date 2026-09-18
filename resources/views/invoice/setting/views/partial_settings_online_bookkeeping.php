<?php
declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var string $tab_index_url
* @var string $quickbooks_credential_url
* @var string $quickbooks_connect_url
* @var bool $quickbooks_connected
*/

$row = ['class' => 'row'];
$colMd8 = ['class' => 'col-12 col-md-8 offset-md-2'];
$panelHead = ['class' => 'card-header'];
$panelBody = ['class' => 'card-body'];
$formGroup = ['class' => 'mb-3'];
$checkbox = ['class' => 'form-check'];
// Same 'settings[bookkeeping_{driver}_' prefix convention
// partial_settings_online_payment.php uses for 'settings[gateway_' --
// QuickBooksGateway's own Settings keys already use bookkeeping_ (not
// gateway_), so this mirrors that naming exactly rather than the
// payment-gateway one.
$pfx = 'settings[bookkeeping_quickbooks_';

/**
 * One field's key => this app's own real Setting key, matching
 * QuickBooksGateway's own private constants exactly.
 */
$fields = [
    'client_id' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.client.id')],
    'client_secret' => ['type' => 'password', 'label' => $translator->translate('bookkeeping.quickbooks.client.secret')],
    'sandbox' => ['type' => 'checkbox', 'label' => $translator->translate('bookkeeping.quickbooks.sandbox')],
    'account_accounts_receivable' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.accounts.receivable')],
    'account_sales' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.sales')],
    'account_vat_or_tax' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.vat.or.tax')],
    'account_bank' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.bank')],
    'account_payment_fees' => ['type' => 'text', 'label' => $translator->translate('bookkeeping.quickbooks.account.payment.fees')],
];

echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2
  // NOT class "gateway-settings" -- that exact class name is queried
  // unconditionally on every page load by settings.ts's
  // handleOnlinePaymentSelectChange() (Online Payment's own show/hide
  // mechanism, called eagerly once "to ensure initial state" regardless
  // of which tab is active) and gets the "hidden" (display: none
  // !important) class added to it unless it also carries
  // "active-gateway" -- confirmed live as the actual root cause of this
  // card silently disappearing. "bookkeeping-settings" avoids the
  // collision entirely; QuickBooks doesn't need that show/hide behaviour
  // anyway, since it's the only provider and is always shown.
  echo H::openTag('div', ['id' => 'bookkeeping-settings-quickbooks', 'class' => 'bookkeeping-settings card']); //3
   echo H::openTag('div', $panelHead); //4
    echo H::openTag('a', [
     'href' => $tab_index_url . '#bookkeeping-settings-quickbooks',
     'class' => 'text-decoration-none text-reset',
    ]);
     echo 'QuickBooks';
    echo H::closeTag('a');
    if ($quickbooks_credential_url !== '') {
    echo H::openTag('a', [
     'href' => $quickbooks_credential_url,
     'target' => '_blank',
     'rel' => 'noopener noreferrer',
     'class' => 'small ms-2',
    ]);
     echo $translator->translate('online.payment.get.credentials');
    echo H::closeTag('a');
    }
    echo H::openTag('span', [
     'class' => 'badge float-end ' . ($quickbooks_connected ? 'bg-success' : 'bg-secondary'),
    ]);
     echo $quickbooks_connected
      ? $translator->translate('bookkeeping.quickbooks.connected')
      : $translator->translate('bookkeeping.quickbooks.not.connected');
    echo H::closeTag('span');
   echo H::closeTag('div'); //4

   echo H::openTag('div', $panelBody); //4

    echo H::openTag('div', $formGroup); //5
     echo H::openTag('a', [
      'href' => $quickbooks_connect_url,
      'class' => 'btn btn-primary',
     ]);
      echo $translator->translate('bookkeeping.quickbooks.connect');
     echo H::closeTag('a');
     echo H::openTag('div', ['class' => 'form-text']); //6
      echo $translator->translate('bookkeeping.quickbooks.connect.hint');
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5

    /**
     * @var string $key
     * @var array{type: string, label: string} $field
     */
    foreach ($fields as $key => $field) {
    $body[$pfx . $key . ']'] = $s->getSetting('bookkeeping_quickbooks_' . $key);

    if ($field['type'] === 'checkbox') {
    echo H::openTag('div', $checkbox); //6
     echo H::openTag('input', [
      'type' => 'hidden',
      'name' => $pfx . $key . ']',
      'value' => '0',
     ]);
     echo H::openTag('input', [
      'type' => 'checkbox',
      'class' => 'form-check-input',
      'id' => $pfx . $key . ']',
      'name' => $pfx . $key . ']',
      'value' => '1',
      'checked' => ($body[$pfx . $key . ']'] === '1') ? 'checked' : null,
     ]);
     echo H::openTag('label', ['class' => 'form-check-label', 'for' => $pfx . $key . ']']);
      echo $field['label'];
     echo H::closeTag('label');
    echo H::closeTag('div'); //6
     continue;
    }

    $fieldId = $pfx . $key . ']';
    $inputValue = (string) $body[$fieldId];
    if ($field['type'] === 'password') {
    try {
     $inputValue = $inputValue !== '' ? (string) $s->decode($inputValue) : '';
    } catch (\App\Invoice\Libraries\CryptorException) {
     $inputValue = '';
    }
    }

    echo H::openTag('div', $formGroup); //6
     echo H::openTag('label', ['for' => $fieldId]);
      echo $field['label'];
     echo H::closeTag('label');
     if ($field['type'] === 'password') {
     echo H::openTag('div', ['class' => 'position-relative']); //7
      echo H::openTag('input', [
       'type' => 'password',
       'class' => 'form-control',
       'style' => 'padding-right: 5rem',
       'name' => $fieldId,
       'id' => $fieldId,
       'value' => $inputValue,
      ]);
      echo H::openTag('button', [
       'type' => 'button',
       'class' => 'btn btn-link p-1 copy-to-clipboard-toggle',
       'style' => 'position: absolute; top: 50%; right: 2.75rem; transform: translateY(-50%);',
       'data-action' => 'copy-to-clipboard',
       'data-copy-target-id' => $fieldId,
       'data-copied-label' => $translator->translate('copied'),
       'aria-label' => $translator->translate('copy.to.clipboard'),
       'title' => $translator->translate('copy.to.clipboard'),
       'tabindex' => '-1',
      ]);
       echo H::tag('i', '', ['class' => 'bi bi-clipboard']);
      echo H::closeTag('button');
      echo H::openTag('button', [
       'type' => 'button',
       'class' => 'btn btn-link position-absolute top-50 end-0 translate-middle-y password-reveal-toggle',
       'data-target' => $fieldId,
       'aria-label' => 'Show password',
       'tabindex' => '-1',
      ]);
       echo H::tag('i', '', ['class' => 'bi bi-eye']);
      echo H::closeTag('button');
     echo H::closeTag('div'); //7
     echo H::openTag('input', [
      'type' => 'hidden',
      'value' => '1',
      'name' => $pfx . $key . '_field_is_password]',
     ]);
     } else {
     echo H::openTag('input', [
      'type' => 'text',
      'class' => 'form-control',
      'name' => $fieldId,
      'id' => $fieldId,
      'value' => $inputValue,
     ]);
     }
    echo H::closeTag('div'); //6
    }

   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
