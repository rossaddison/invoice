<?php

declare(strict_types=1);

use App\Invoice\Peppol\PeppolSendService;

/**
 * Oxalis base URL — set OXALIS_BASE_URL in .env to override.
 * Phase A (mock):  http://localhost:8181
 * Phase B (real):  http://localhost:8080  (or wherever Oxalis listens)
 *
 * PEPPOL_SENDER_ID — your Peppol participant ID, e.g. 0088:1234567890123
 * Leave blank to omit the SenderId part (Oxalis may derive it from your cert).
 */
$oxalisBaseUrl = rtrim(
    $_ENV['OXALIS_BASE_URL'] ?? 'http://localhost:8181',
    '/'
);

$senderParticipantId = $_ENV['PEPPOL_SENDER_ID'] ?? '';

return [
    PeppolSendService::class => [
        'class' => PeppolSendService::class,
        '__construct()' => [
            'oxalisBaseUrl'       => $oxalisBaseUrl,
            'senderParticipantId' => $senderParticipantId,
        ],
    ],
];
