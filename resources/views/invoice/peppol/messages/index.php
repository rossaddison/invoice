<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
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

$statusBadge = static function (string $status): string {
    $class = match (strtoupper($status)) {
        'QUEUED'    => 'bg-secondary',
        'SENDING'   => 'bg-info text-dark',
        'SENT'      => 'bg-primary',
        'DELIVERED' => 'bg-success',
        'FAILED'    => 'bg-danger',
        default     => 'bg-light text-dark border',
    };
    return '<span class="badge ' . $class . '">' . H::encode($status) . '</span>';
};

echo H::openTag('div', ['class' => 'container-fluid py-3']);

echo H::openTag('div', ['class' => 'd-flex align-items-center justify-content-between mb-3']);
echo H::tag('h4', H::encode('Peppol Message Log'), ['class' => 'mb-0']);
echo H::a(
    H::tag('i', '', ['class' => 'bi bi-arrow-clockwise me-1']) . H::encode('Refresh'),
    $urlGenerator->generate('peppol/messages/index'),
    ['class' => 'btn btn-outline-secondary btn-sm']
)->encode(false)->render();
echo H::closeTag('div');

// Plain GET filter form — PeppolMessageRepository::filterCombined()
// reads these same param names straight off the query string, the same
// filterCombined(array $queryParams) shape ProductRepository already
// uses. No GridView/DataColumn filter machinery here since this is a
// hand-rolled table, not a GridView grid.
echo H::openTag('form', ['method' => 'get',
 'action' => $urlGenerator->generate('peppol/messages/index'),
 'class' => 'row row-cols-lg-auto g-2 align-items-center mb-3']);
foreach ([
 'status' => 'Search Status',
 'message_id' => 'Search Message ID',
 'recipient_id' => 'Search Receiver',
 'inv_id' => 'Search Invoice #',
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
echo H::tag(
    'button',
    'Filter',
    ['type' => 'submit', 'class' => 'btn btn-primary btn-sm']
);
echo H::closeTag('div');
echo H::openTag('div', ['class' => 'col-12']);
echo H::a(
    'Clear',
    $urlGenerator->generate('peppol/messages/index'),
    ['class' => 'btn btn-outline-secondary btn-sm']
)->render();
echo H::closeTag('div');
echo H::closeTag('form');

echo H::openTag('div', ['class' => 'table-responsive']);
echo H::openTag('table', ['class' => 'table table-hover table-sm align-middle']);
echo H::openTag('thead', ['class' => 'table-dark']);
echo H::openTag('tr');
foreach (['#', 'Inv', 'Status', 'Message ID', 'Recipient', 'Retries', 'Sent At', 'Delivered At', 'Created'] as $col) {
    echo H::tag('th', H::encode($col));
}
echo H::tag('th', '');
echo H::closeTag('tr');
echo H::closeTag('thead');
echo H::openTag('tbody');

/** @var PeppolMessage $message */
foreach ($messages as $message) {
    $sentAt      = $message->getSentAt();
    $deliveredAt = $message->getDeliveredAt();
    $msgId       = $message->getMessageId();

    echo H::openTag('tr');
    echo H::tag('td', H::encode((string) $message->reqId()), ['class' => 'text-muted small']);
    $invId = $message->getInvId();
    echo H::tag(
        'td',
        $invId !== null
       ? H::a(H::encode('#' . $invId), $urlGenerator->generate('inv/view', ['id' => $invId]))->render()
       : '—',
        ['class' => 'text-nowrap']
    )->encode(false)->render();
    echo H::tag('td', $statusBadge($message->getStatus()), ['class' => 'text-nowrap'])->encode(false)->render();
    echo H::tag(
        'td',
        $msgId !== null
       ? H::tag(
           'code',
           H::encode(mb_strimwidth($msgId, 0, 36, '…')),
           ['title' => H::encode($msgId)]
       )->encode(false)->render()
       : H::tag('span', '—', ['class' => 'text-muted'])->render(),
        ['class' => 'small']
    )->encode(false)->render();
    echo H::tag('td', H::encode($message->getRecipientId() ?? '—'), ['class' => 'small text-nowrap']);
    echo H::tag('td', H::encode((string) $message->getRetryCount()), ['class' => 'text-center']);
    echo H::tag(
        'td',
        $sentAt !== null ? H::encode($sentAt->format('Y-m-d H:i')) : H::tag('span', '—', ['class' => 'text-muted'])->render(),
        ['class' => 'small text-nowrap']
    )->encode(false)->render();
    echo H::tag(
        'td',
        $deliveredAt !== null ? H::encode($deliveredAt->format('Y-m-d H:i')) : H::tag('span', '—', ['class' => 'text-muted'])->render(),
        ['class' => 'small text-nowrap']
    )->encode(false)->render();
    echo H::tag(
        'td',
        H::encode($message->getCreatedAt()->format('Y-m-d H:i')),
        ['class' => 'small text-nowrap text-muted']
    );
    echo H::tag(
        'td',
        H::a(
            'Detail',
            $urlGenerator->generate('peppol/messages/view', ['id' => $message->reqId()]),
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
