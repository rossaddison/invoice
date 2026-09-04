<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use Yiisoft\Html\Html as H;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var PeppolMessage $message
 * @var TranslatorInterface $translator
 * @var UrlGeneratorInterface $urlGenerator
 */

$statusClass = match (strtoupper($message->getStatus())) {
    'QUEUED'    => 'bg-secondary',
    'SENDING'   => 'bg-info text-dark',
    'SENT'      => 'bg-primary',
    'DELIVERED' => 'bg-success',
    'FAILED'    => 'bg-danger',
    default     => 'bg-light text-dark border',
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
    H::tag('i', '', ['class' => 'bi bi-arrow-left me-1']) . H::encode('Peppol Log'),
    $urlGenerator->generate('peppol/messages/index'),
    ['class' => 'btn btn-outline-secondary btn-sm']
)->encode(false)->render();
echo H::tag(
    'h4',
    'Peppol Message #' . H::encode((string) $message->reqId())
   . ' <span class="badge ' . $statusClass . ' ms-2">' . H::encode($message->getStatus()) . '</span>',
    ['class' => 'mb-0']
)->encode(false)->render();
echo H::closeTag('div');

// ── Identity ─────────────────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::tag('div', 'Identity', ['class' => 'card-header fw-bold']);
echo H::openTag('div', ['class' => 'card-body p-0']);
echo H::openTag('table', ['class' => 'table table-sm table-borderless mb-0']);
echo $row('Message ID', $message->getMessageId() ?? '—');
$invId = $message->getInvId();
echo H::openTag('tr')
 . H::tag('th', H::encode('Invoice'), ['class' => 'text-muted fw-normal w-25'])
 . H::tag(
     'td',
     $invId !== null
     ? H::a(H::encode('#' . $invId), $urlGenerator->generate('inv/view', ['id' => $invId]))->render()
     : '—'
 )->encode(false)->render()
 . H::closeTag('tr');
echo $row('Recipient ID', $message->getRecipientId() ?? '—');
echo $row('Document Type', $message->getDocumentTypeId() ?? '—');
echo $row('Process ID', $message->getProcessId() ?? '—');
echo H::closeTag('table');
echo H::closeTag('div');
echo H::closeTag('div');

// ── Delivery ─────────────────────────────────────────────────────────────
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::tag('div', 'Delivery', ['class' => 'card-header fw-bold']);
echo H::openTag('div', ['class' => 'card-body p-0']);
echo H::openTag('table', ['class' => 'table table-sm table-borderless mb-0']);
$sentAt = $message->getSentAt();
echo $row('Sent At', $sentAt !== null ? $sentAt->format('Y-m-d H:i:s') : '—');
$deliveredAt = $message->getDeliveredAt();
echo $row('Delivered At', $deliveredAt !== null ? $deliveredAt->format('Y-m-d H:i:s') : '—');
echo $row('Retry Count', (string) $message->getRetryCount());
echo $row('Created At', $message->getCreatedAt()->format('Y-m-d H:i:s'));
echo H::closeTag('table');
echo H::closeTag('div');
echo H::closeTag('div');

// ── Error ─────────────────────────────────────────────────────────────────
$errMsg = $message->getErrorMessage();
if ($errMsg !== null) {
    echo H::openTag('div', ['class' => 'card mb-3 border-danger']);
    echo H::tag('div', 'Error', ['class' => 'card-header fw-bold bg-danger text-white']);
    echo H::openTag('div', ['class' => 'card-body']);
    echo H::tag('pre', H::encode($errMsg), ['class' => 'mb-0 small']);
    echo H::closeTag('div');
    echo H::closeTag('div');
}

// ── UBL XML ──────────────────────────────────────────────────────────────
$ublXml = $message->getUblXml();
if ($ublXml !== null) {
    echo H::openTag('div', ['class' => 'card mb-3']);
    echo H::openTag('div', ['class' => 'card-header fw-bold d-flex justify-content-between align-items-center']);
    echo H::encode('UBL XML');
    // data-action wiring, not a raw onclick — see
    // partial_settings_system_updates.php for the established
    // data-actions.ts copy-to-clipboard convention this matches; a raw
    // inline event handler here would be exactly the CSP unsafe-inline
    // pattern project_csp_unsafe_inline_fix already eliminated elsewhere.
    echo H::tag(
        'button',
        'Copy',
        [
      'type'  => 'button',
      'class' => 'btn btn-outline-secondary btn-sm',
      'data-action' => 'copy-to-clipboard',
      'data-copy-target' => '#ubl-xml-pre',
     ]
    );
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body p-0']);
    echo H::tag(
        'pre',
        H::encode($ublXml),
        ['id' => 'ubl-xml-pre', 'class' => 'mb-0 p-3 small', 'style' => 'max-height:400px;overflow:auto']
    );
    echo H::closeTag('div');
    echo H::closeTag('div');
}

echo H::closeTag('div');
