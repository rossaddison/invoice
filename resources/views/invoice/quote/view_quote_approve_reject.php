<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Option;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 * @var array $quoteStatuses draft 1 ... sent 2 ... viewed 3 ... approved 4 ... rejected 5 ... cancelled 6
 * @var bool $invEdit
 * @var string $sales_order_number
 */

echo H::openTag('div', ['class' => 'quote-properties']); //0
 echo H::openTag('label', ['for' => 'status_id']); //1
  echo $translator->translate('status');
 echo H::closeTag('label'); //1
 echo H::openTag('select', [
  'name' => 'status_id',
  'id' => 'status_id',
  'disabled' => true,
  'class' => 'form-control form-control-lg',
 ]); //1
  /**
   * @var string $key
   * @var array $status
   * @var string $status['label']
   */
  foreach ($quoteStatuses as $key => $status) {
   echo new Option()
    ->value($key)
    ->selected($key === $body['status_id'])
    ->content(H::encode($status['label']));
  }
 echo H::closeTag('select'); //1
echo H::closeTag('div'); //0

echo H::openTag('div', ['class' => 'quote-properties']); //0
 echo H::openTag('label', ['for' => 'quote_password', 'hidden' => true]); //1
  echo $translator->translate('quote.password');
 echo H::closeTag('label'); //1
 echo H::tag('input', '', [
  'type' => 'text',
  'id' => 'quote_password',
  'class' => 'form-control form-control-lg',
  'disabled' => true,
  'value' => H::encode($body['password'] ?? ''),
  'hidden' => true,
 ]);
echo H::closeTag('div'); //0

// draft => show the url
if ($quote->getStatusId() == 1) {
 echo H::openTag('div', ['class' => 'quote-properties']); //0
  echo H::openTag('label', ['for' => 'quote_guest_url', 'hidden' => true]); //1
   echo $translator->translate('guest.url');
  echo H::closeTag('label'); //1
  echo H::openTag('div', ['class' => 'input-group', 'hidden' => true]); //1
   echo H::tag('input', '', [
    'type' => 'text',
    'id' => 'quote_guest_url',
    'disabled' => true,
    'class' => 'form-control form-control-lg',
    'value' => $quote->getUrlKey(),
   ]);
   echo H::openTag('span', [
    'class' => 'input-group-text to-clipboard cursor-pointer',
    'data-clipboard-target' => '#quote_guest_url',
   ]); //2
    echo H::openTag('i', ['class' => 'bi bi-clipboard']); //3
    echo H::closeTag('i'); //3
   echo H::closeTag('span'); //2
  echo H::closeTag('div'); //1
 echo H::closeTag('div'); //0
}

// sent 2 or viewed 3 or rejected 5 AND no sales order => approve before transferring to sales order
if (($quote->getStatusId() === 2 ||
     $quote->getStatusId() === 3 ||
     $quote->getStatusId() === 5) &&
     !$invEdit && ($quote->getSoId() === '0' || empty($quote->getSoId()))) {
 echo H::openTag('div'); //0
  echo H::tag('br', '');
  echo H::a(
   $translator->translate('approve.this.quote'),
   $urlGenerator->generate('quote/urlKey', ['url_key' => $quote->getUrlKey()]),
   ['class' => 'btn btn-success']
  );
 echo H::closeTag('div'); //0
}

// sent 2 or viewed 3 or approved 4 AND user not permission to edit AND no sales order => can be rejected
if (($quote->getStatusId() === 2 ||
     $quote->getStatusId() === 3 ||
     $quote->getStatusId() === 4) &&
     !$invEdit && ($quote->getSoId() === '0' || empty($quote->getSoId()))) {
 echo H::openTag('div'); //0
  echo H::tag('br', '');
  echo H::a(
   $translator->translate('reject.this.quote'),
   $urlGenerator->generate('quote/urlKey', ['url_key' => $quote->getUrlKey()]),
   ['class' => 'btn btn-danger']
  );
 echo H::closeTag('div'); //0
}

echo H::tag('input', '', [
 'type' => 'text',
 'id' => 'dropzone_client_id',
 'readonly' => true,
 'hidden' => true,
 'class' => 'form-control form-control-lg',
 'value' => $quote->getClient()?->getClientId(),
]);

// the quote has already been approved because it has a sales order number associated with it => it can only be viewed
if ($quote->getSoId()) {
 echo H::openTag('div'); //0
  echo H::openTag('label', ['for' => 'salesorder_to_url']); //1
   echo $translator->translate('salesorder');
  echo H::closeTag('label'); //1
  echo H::openTag('div', ['class' => 'input-group']); //1
   echo H::a(
    $sales_order_number,
    $urlGenerator->generate('salesorder/view', ['id' => $quote->getSoId()]),
    ['class' => 'btn btn-success']
   );
  echo H::closeTag('div'); //1
 echo H::closeTag('div'); //0
}
