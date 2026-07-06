<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Writer\DataWriterInterface;
use App\Invoice\As4\As4DuplicateDetector;
use App\Invoice\As4\As4DuplicateDetectorInterface;
use App\Invoice\As4\As4EnvelopeBuilderInterface;
use App\Invoice\As4\As4EnvelopeSignerInterface;
use App\Invoice\As4\As4ExponentialBackoffRetryPolicy;
use App\Invoice\As4\As4FixedIntervalRetryPolicy;
use App\Invoice\As4\As4InvoiceImportService;
use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4PayloadHandlerInterface;
use App\Invoice\As4\As4ReceiptGenerator;
use App\Invoice\As4\As4ReceiptGeneratorInterface;
use App\Invoice\As4\As4ReceiptParser;
use App\Invoice\As4\As4ReceiptParserInterface;
use App\Invoice\As4\As4RetryPolicyInterface;
use App\Invoice\As4\As4HttpClient;
use App\Invoice\As4\As4HttpTransportInterface;
use App\Invoice\As4\As4Sender;
use App\Invoice\As4\As4SenderInterface;
use App\Invoice\As4\As4SmpResolverInterface;
use App\Invoice\As4\As4UserMessageHandlerInterface;
use App\Invoice\As4\As4UserMessageHandlerService;
use App\Invoice\As4\SoapEnvelopeBuilder;
use App\Invoice\As4\StaticAs4SmpResolver;
use App\Invoice\As4\WsSecuritySigner;

// ── Signing certificate & private key ─────────────────────────────────────────
// Generate on each node:
//   openssl genrsa -out /etc/as4/signing-key.pem 4096
//   openssl req -new -x509 -key /etc/as4/signing-key.pem \
//     -out /etc/as4/signing-cert.pem -days 365 \
//     -subj "/C=GB/O=Yii3-i/CN=bilateral-node"
$signingKeyPath  = $_ENV['AS4_SIGNING_KEY_PATH']  ?? '';
$signingCertPath = $_ENV['AS4_SIGNING_CERT_PATH'] ?? '';

$signingKeyPem  = $signingKeyPath  !== '' && is_readable($signingKeyPath)
    ? (string) file_get_contents($signingKeyPath)
    : '';
$signingCertPem = $signingCertPath !== '' && is_readable($signingCertPath)
    ? (string) file_get_contents($signingCertPath)
    : '';

// ── Peer (other node) configuration ───────────────────────────────────────────
// Exchange signing-cert.pem with the other node and set AS4_PEER_CERT_PATH.
$peerCertPath  = $_ENV['AS4_PEER_CERT_PATH']         ?? '';
$peerEndpoint  = $_ENV['AS4_PEER_ENDPOINT']           ?? '';
$peerTransport = $_ENV['AS4_PEER_TRANSPORT_PROFILE']  ?? 'bilateral-as4-test';

$peerCertPem = $peerCertPath !== '' && is_readable($peerCertPath)
    ? (string) file_get_contents($peerCertPath)
    : '';

// ── This node's Peppol/bilateral party ID ─────────────────────────────────────
// Bilateral example : "bilateral:localhost"  or  "bilateral:yii3i.online"
// Peppol example    : "0088:1234567890123"
$senderPartyId = $_ENV['AS4_SENDER_PARTY_ID'] ?? 'bilateral:unknown';

// ── Retry policy ─────────────────────────────────────────────────────────────
// 'exponential' → exponential back-off; anything else → fixed interval
$retryPolicy = ($_ENV['AS4_RETRY_POLICY'] ?? 'fixed') === 'exponential'
    ? As4ExponentialBackoffRetryPolicy::class
    : As4FixedIntervalRetryPolicy::class;

return [
    // ── Inbound interfaces (always active) ────────────────────────────────────
    DataWriterInterface::class            => EntityWriter::class,
    As4MessageRepositoryInterface::class  => CycleOrmAs4MessageRepository::class,
    As4DuplicateDetectorInterface::class  => As4DuplicateDetector::class,
    As4ReceiptGeneratorInterface::class   => As4ReceiptGenerator::class,
    As4UserMessageHandlerInterface::class => As4UserMessageHandlerService::class,
    As4PayloadHandlerInterface::class     => As4InvoiceImportService::class,

    // ── Outbound interfaces ───────────────────────────────────────────────────
    As4HttpTransportInterface::class    => As4HttpClient::class,
    As4SenderInterface::class          => As4Sender::class,
    As4EnvelopeBuilderInterface::class => SoapEnvelopeBuilder::class,
    As4ReceiptParserInterface::class   => As4ReceiptParser::class,
    As4RetryPolicyInterface::class     => $retryPolicy,

    // WsSecuritySigner — needs PEM strings from the cert files above
    WsSecuritySigner::class => [
        'class' => WsSecuritySigner::class,
        '__construct()' => [
            'privateKeyPem'  => $signingKeyPem,
            'certificatePem' => $signingCertPem,
        ],
    ],
    As4EnvelopeSignerInterface::class => WsSecuritySigner::class,

    // StaticAs4SmpResolver — bilateral mode: returns the configured peer endpoint
    // Swap to As4SmpResolver::class for real Peppol 4-corner with SMP lookup
    StaticAs4SmpResolver::class => [
        'class' => StaticAs4SmpResolver::class,
        '__construct()' => [
            'endpointUrl'      => $peerEndpoint,
            'certificatePem'   => $peerCertPem,
            'transportProfile' => $peerTransport,
        ],
    ],
    As4SmpResolverInterface::class => StaticAs4SmpResolver::class,

    // As4MessageDispatcher — needs the scalar senderPartyId wired explicitly
    \App\Invoice\As4\As4MessageDispatcher::class => [
        'class' => \App\Invoice\As4\As4MessageDispatcher::class,
        '__construct()' => [
            'senderPartyId' => $senderPartyId,
        ],
    ],
];
