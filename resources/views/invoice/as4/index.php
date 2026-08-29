<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Invoice\As4\As4MessageState;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Html\Html as H;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var EntityReader $messages
 * @var TranslatorInterface $translator
 * @var UrlGeneratorInterface $urlGenerator
 * @var array<array-key, mixed> $queryParams
 */

$q = static function (string $key) use ($queryParams): string {
    return isset($queryParams[$key]) && is_string($queryParams[$key]) ? $queryParams[$key] : '';
};

$stateBadge = static function (As4MessageState $state): string {
    $class = match ($state) {
        As4MessageState::pending         => 'bg-secondary',
        As4MessageState::sent            => 'bg-info text-dark',
        As4MessageState::receiptReceived => 'bg-primary',
        As4MessageState::delivered       => 'bg-success',
        As4MessageState::failed          => 'bg-danger',
        As4MessageState::duplicate       => 'bg-warning text-dark',
        As4MessageState::received        => 'bg-teal text-white',
    };
    return '<span class="badge ' . $class . '">' . H::encode($state->value) . '</span>';
};

echo H::openTag('div', ['class' => 'container-fluid py-3']);

 echo H::openTag('div', ['class' => 'd-flex align-items-center justify-content-between mb-3']);
  echo H::tag('h4', H::encode('AS4 Message Log'), ['class' => 'mb-0']);
  echo H::a(
   H::tag('i', '', ['class' => 'bi bi-arrow-clockwise me-1']) . H::encode('Refresh'),
   $urlGenerator->generate('as4message/index'),
   ['class' => 'btn btn-outline-secondary btn-sm']
  )->encode(false)->render();
 echo H::closeTag('div');

 // Same plain-GET-form approach as peppol/messages/index.php — see that
 // file's comment for why there's no GridView/DataColumn filter here.
 echo H::openTag('form', ['method' => 'get',
  'action' => $urlGenerator->generate('as4message/index'),
  'class' => 'row row-cols-lg-auto g-2 align-items-center mb-3']);
  foreach ([
   'state' => 'Search State',
   'message_id' => 'Search Message ID',
   'sender_party_id' => 'Search Sender',
   'receiver_party_id' => 'Search Receiver',
  ] as $name => $placeholder) {
   echo H::openTag('div', ['class' => 'col-12']);
    echo H::openTag('input', [
     'type' => 'text',
     'name' => $name,
     'value' => $q($name),
     'class' => 'form-control form-control-sm',
     'placeholder' => $placeholder,
    ]);
   echo H::closeTag('div');
  }
  echo H::openTag('div', ['class' => 'col-12']);
   echo H::tag('button', 'Filter',
    ['type' => 'submit', 'class' => 'btn btn-primary btn-sm']);
  echo H::closeTag('div');
  echo H::openTag('div', ['class' => 'col-12']);
   echo H::a('Clear', $urlGenerator->generate('as4message/index'),
    ['class' => 'btn btn-outline-secondary btn-sm'])->render();
  echo H::closeTag('div');
 echo H::closeTag('form');

 echo H::openTag('div', ['class' => 'table-responsive']);
  echo H::openTag('table', ['class' => 'table table-hover table-sm align-middle']);
   echo H::openTag('thead', ['class' => 'table-dark']);
    echo H::openTag('tr');
     foreach (['#', 'State', 'Message ID', 'Sender', 'Receiver', 'Attempts', 'Last Attempt', 'Created'] as $col) {
      echo H::tag('th', H::encode($col));
     }
     echo H::tag('th', '');
    echo H::closeTag('tr');
   echo H::closeTag('thead');
   echo H::openTag('tbody');

   /** @var As4Message $message */
   foreach ($messages as $message) {
    $routing     = $message->getRouting();
    $retryState  = $message->getRetryState();
    $timestamps  = $message->getTimestamps();
    $lastAttempt = $retryState->getLastAttemptAt();

    echo H::openTag('tr');
     echo H::tag('td', H::encode((string) $message->reqId()), ['class' => 'text-muted small']);
     echo H::tag('td', $stateBadge($message->getState()), ['class' => 'text-nowrap'])->encode(false)->render();
     echo H::tag('td',
      H::tag('code',
       H::encode(mb_strimwidth($message->getMessageId(), 0, 40, '…')),
       ['title' => H::encode($message->getMessageId())]
      )->encode(false)->render(),
      ['class' => 'small']
     )->encode(false)->render();
     echo H::tag('td', H::encode($routing->getSenderPartyId()), ['class' => 'small text-nowrap']);
     echo H::tag('td', H::encode($routing->getReceiverPartyId()), ['class' => 'small text-nowrap']);
     echo H::tag('td',
      H::encode($retryState->getAttemptCount() . ' / ' . $retryState->getMaxAttempts()),
      ['class' => 'text-center']
     );
     echo H::tag('td',
      $lastAttempt !== null ? H::encode($lastAttempt->format('Y-m-d H:i')) : H::tag('span', '—', ['class' => 'text-muted']),
      ['class' => 'small text-nowrap']
     )->encode(false)->render();
     echo H::tag('td',
      H::encode($timestamps->getCreatedAt()->format('Y-m-d H:i')),
      ['class' => 'small text-nowrap text-muted']
     );
     echo H::tag('td',
      H::a('Detail', $urlGenerator->generate('as4message/view', ['id' => $message->reqId()]),
       ['class' => 'btn btn-outline-primary btn-sm']
      )->render(),
      ['class' => 'text-nowrap']
     )->encode(false)->render();
    echo H::closeTag('tr');
   }

   echo H::closeTag('tbody');
  echo H::closeTag('table');
 echo H::closeTag('div');

echo H::closeTag('div');
