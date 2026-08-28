<?php

declare(strict_types=1);

use App\Invoice\Peppol\OxalisPeppolSendService;
use App\Invoice\Peppol\PeppolMessageRepository;
use App\Invoice\Peppol\PeppolMessageRepositoryInterface;
use App\Invoice\Peppol\PeppolSendServiceInterface;
use App\Invoice\Peppol\PeppolSendServiceRouter;
use App\Invoice\Peppol\SmpResolver;
use App\Invoice\Peppol\SmpResolverInterface;

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

/**
 * PEPPOL_SML_ZONE — DNS zone for the SML.
 * Production : edelivery.tech.ec.europa.eu
 * Acceptance : acc.edelivery.tech.ec.europa.eu
 *
 * PEPPOL_SMP_BASE_URL — set to bypass DNS and point directly at an SMP host,
 * e.g. http://smp.example.com  (useful in development/test environments).
 */
$smlZone    = $_ENV['PEPPOL_SML_ZONE']    ?? 'edelivery.tech.ec.europa.eu';
$smpBaseUrl = $_ENV['PEPPOL_SMP_BASE_URL'] ?? null;

return [
    PeppolMessageRepositoryInterface::class => PeppolMessageRepository::class,

    SmpResolverInterface::class => SmpResolver::class,

    SmpResolver::class => [
        'class' => SmpResolver::class,
        '__construct()' => [
            'smlZone'    => $smlZone,
            'smpBaseUrl' => $smpBaseUrl,
        ],
    ],

    OxalisPeppolSendService::class => [
        'class' => OxalisPeppolSendService::class,
        '__construct()' => [
            'oxalisBaseUrl'       => $oxalisBaseUrl,
            'senderParticipantId' => $senderParticipantId,
        ],
    ],

    // StorecovePeppolSendService has no scalar constructor params (just
    // PeppolMessageRepositoryInterface + SettingRepository, both already
    // autowirable), so it needs no entry of its own here.

    // Which one actually sends is decided per-call by
    // PeppolSendServiceRouter reading peppol_access_point_provider — see
    // that class's own docblock for why this isn't a static choice made
    // once here at container-build time.
    PeppolSendServiceInterface::class => PeppolSendServiceRouter::class,
];
