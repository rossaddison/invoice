<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Invoice\As4\As4MessageState;
use Yiisoft\Html\Html as H;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var As4Message $message
 * @var TranslatorInterface $translator
 * @var UrlGeneratorInterface $urlGenerator
 */

$routing    = $message->getRouting();
$retryState = $message->getRetryState();
$receipt    = $message->getReceiptInfo();
$error      = $message->getErrorInfo();
$timestamps = $message->getTimestamps();

$stateClass = match ($message->getState()) {
    As4MessageState::pending         => 'bg-secondary',
    As4MessageState::sent            => 'bg-info text-dark',
    As4MessageState::receiptReceived => 'bg-primary',
    As4MessageState::delivered       => 'bg-success',
    As4MessageState::failed          => 'bg-danger',
    As4MessageState::duplicate       => 'bg-warning text-dark',
    As4MessageState::received        => 'bg-teal text-white',
};

$row = static function (string $label, string $value): string {
    return H::openTag('tr')
        . H::tag('th', H::encode($label), ['class' => 'text-muted fw-normal w-25'])
        . H::tag('td', H::encode($value))
        . H::closeTag('tr');
};

echo H::openTag('div', ['class' => 'container-fluid py-3']);

echo H::openTag('div', ['class' => 'd-flex align-items-center gap-3 mb-3']);
echo H::a(
    H::tag('i', '', ['class' => 'bi bi-arrow-left me-1']) . H::encode('AS4 Log'),
    $urlGenerator->generate('as4message/index'),
    ['class' => 'btn btn-outline-secondary btn-sm']
)->encode(false)->render();
echo H::tag(
    'h4',
    'Message #' . H::encode((string) $message->reqId())
   . ' <span class="badge ' . $stateClass . ' ms-2">' . H::encode($message->getState()->value) . '</span>',
    ['class' => 'mb-0']
)->encode(false)->render();
echo H::closeTag('div');

// ── Identity ─────────────────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::tag('div', 'Identity', ['class' => 'card-header fw-bold']);
echo H::openTag('div', ['class' => 'card-body p-0']);
echo H::openTag('table', ['class' => 'table table-sm table-borderless mb-0']);
echo $row('Message ID', $message->getMessageId());
echo $row('Conversation ID', $routing->getConversationId());
echo H::closeTag('table');
echo H::closeTag('div');
echo H::closeTag('div');

// ── Routing ──────────────────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::tag('div', 'Routing', ['class' => 'card-header fw-bold']);
echo H::openTag('div', ['class' => 'card-body p-0']);
echo H::openTag('table', ['class' => 'table table-sm table-borderless mb-0']);
echo $row('Sender Party ID', $routing->getSenderPartyId());
echo $row('Sender Role', $routing->getSenderRole());
echo $row('Receiver Party ID', $routing->getReceiverPartyId());
echo $row('Receiver Role', $routing->getReceiverRole());
echo $row('Receiver Endpoint', $routing->getReceiverEndpoint());
echo $row('Service', $routing->getService());
echo $row('Action', $routing->getAction());
echo H::closeTag('table');
echo H::closeTag('div');
echo H::closeTag('div');

// ── Retry State ──────────────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::tag('div', 'Retry State', ['class' => 'card-header fw-bold']);
echo H::openTag('div', ['class' => 'card-body p-0']);
echo H::openTag('table', ['class' => 'table table-sm table-borderless mb-0']);
echo $row('Attempts', $retryState->getAttemptCount() . ' / ' . $retryState->getMaxAttempts());
echo $row('Retry Interval', $retryState->getRetryIntervalSeconds() . 's');
$firstSent = $retryState->getFirstSentAt();
echo $row('First Sent', $firstSent !== null ? $firstSent->format('Y-m-d H:i:s') : '—');
$lastAttempt = $retryState->getLastAttemptAt();
echo $row('Last Attempt', $lastAttempt !== null ? $lastAttempt->format('Y-m-d H:i:s') : '—');
$nextRetry = $message->getNextRetryIn();
echo $row('Next Retry In', $nextRetry !== null ? $nextRetry . 's' : '—');
echo H::closeTag('table');
echo H::closeTag('div');
echo H::closeTag('div');

// ── Receipt ──────────────────────────────────────────────────────────────
$receiptAt = $receipt->getReceiptReceivedAt();
if ($receipt->getReceiptMessageId() !== null || $receiptAt !== null) {
    echo H::openTag('div', ['class' => 'card mb-3']);
    echo H::tag('div', 'Receipt', ['class' => 'card-header fw-bold bg-success-subtle']);
    echo H::openTag('div', ['class' => 'card-body p-0']);
    echo H::openTag('table', ['class' => 'table table-sm table-borderless mb-0']);
    echo $row('Receipt Message ID', $receipt->getReceiptMessageId() ?? '—');
    echo $row('Digest', $receipt->getReceiptDigest() ?? '—');
    echo $row('Received At', $receiptAt !== null ? $receiptAt->format('Y-m-d H:i:s') : '—');
    echo H::closeTag('table');
    echo H::closeTag('div');
    echo H::closeTag('div');
}

// ── Error ─────────────────────────────────────────────────────────────────
if ($error->getErrorCode() !== null) {
    echo H::openTag('div', ['class' => 'card mb-3 border-danger']);
    echo H::tag('div', 'Error', ['class' => 'card-header fw-bold bg-danger text-white']);
    echo H::openTag('div', ['class' => 'card-body p-0']);
    echo H::openTag('table', ['class' => 'table table-sm table-borderless mb-0']);
    echo $row('Code', $error->getErrorCode() ?? '');
    echo $row('Description', $error->getErrorDescription() ?? '');
    echo H::closeTag('table');
    echo H::closeTag('div');
    echo H::closeTag('div');
}

// ── Timestamps ────────────────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::tag('div', 'Timestamps', ['class' => 'card-header fw-bold']);
echo H::openTag('div', ['class' => 'card-body p-0']);
echo H::openTag('table', ['class' => 'table table-sm table-borderless mb-0']);
echo $row('Created', $timestamps->getCreatedAt()->format('Y-m-d H:i:s'));
echo $row('Updated', $timestamps->getUpdatedAt()->format('Y-m-d H:i:s'));
echo H::closeTag('table');
echo H::closeTag('div');
echo H::closeTag('div');

echo H::closeTag('div');
