<?php

declare(strict_types=1);

namespace Invoice\As4;

/**
 * P-Mode (Processing Mode) Configuration
 * 
 * Encapsulates AS4 message processing configuration per eDelivery AS4 2.0
 * section 3.5 P-Mode Parameters table.
 * 
 * P-Mode governs:
 * - Message Exchange Patterns (OneWay/Push, TwoWay/Push-and-Push, etc.)
 * - Security (signing/encryption certificates and algorithms)
 * - Reliability (retry, duplicate detection)
 * - Compression
 * - Error handling
 */
class PMode
{
    // Initiator configuration
    private string $initiatorParty;
    private string $initiatorRole = '';
    private string $initiatorPartyType = 'urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088';

    // Responder configuration
    private string $responderParty;
    private string $responderRole = '';
    private string $responderPartyType = 'urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088';
    private string $responderProtocolAddress; // HTTPS URL

    // Business info
    private string $service;
    private string $action;
    private string $mep = As4Constants::MEP_ONE_WAY;
    private string $mepBinding = As4Constants::MEPBINDING_PUSH;
    private string $mpc = As4Constants::DEFAULT_MPC;

    // Security
    private bool $securityEnabled = true;
    private bool $signEnabled = true;
    private string $signCertificate = '';
    private string $signAlgorithm = As4Constants::SIGNATURE_ALGORITHM;
    private string $signHashAlgorithm = As4Constants::HASH_ALGORITHM;

    private bool $encryptEnabled = true;
    private string $encryptCertificate = '';
    private string $encryptAlgorithm = As4Constants::ENCRYPTION_ALGORITHM;
    private string $keyAgreement = As4Constants::KEY_AGREEMENT;
    private string $keyWrapping = As4Constants::KEY_WRAPPING;
    private string $keyDerivation = As4Constants::KEY_DERIVATION;
    private string $keyDerivationPrf = As4Constants::KEY_DERIVATION_PRF;

    // Receipts (Non-Repudiation)
    private bool $sendReceipt = true;
    private bool $receiptNonRepudiation = true;
    private string $receiptReplyPattern = 'Response'; // Response | Callback

    // Error handling
    private bool $reportAsResponse = true;
    private bool $processErrorNotifyConsumer = true;
    private bool $processErrorNotifyProducer = true;
    private bool $missingReceiptNotifyProducer = true;

    // Reception Awareness (Reliable Messaging)
    private bool $receptionAwarenessEnabled = true;
    private bool $retryEnabled = true;
    private int $retryMaxRetries = 3;
    private int $retryIntervalSeconds = 300;

    private bool $duplicateDetectionEnabled = true;

    // Compression
    private bool $compressionEnabled = true;
    private string $compressionType = As4Constants::COMPRESSION_TYPE;

    // Four Corner Topology (optional)
    private bool $fourCornerEnabled = false;

    // WS-Security version
    private string $wssVersion = '1.1.1';

    public function __construct(
        string $initiatorParty,
        string $responderParty,
        string $responderProtocolAddress,
        string $service,
        string $action
    ) {
        $this->initiatorParty = $initiatorParty;
        $this->responderParty = $responderParty;
        $this->responderProtocolAddress = $responderProtocolAddress;
        $this->service = $service;
        $this->action = $action;
    }

    // Getters and setters
    public function getInitiatorParty(): string { return $this->initiatorParty; }
    public function setInitiatorRole(string $role): self { $this->initiatorRole = $role; return $this; }
    public function getInitiatorRole(): string { return $this->initiatorRole; }

    public function getResponderParty(): string { return $this->responderParty; }
    public function getResponderProtocolAddress(): string { return $this->responderProtocolAddress; }
    public function setResponderRole(string $role): self { $this->responderRole = $role; return $this; }
    public function getResponderRole(): string { return $this->responderRole; }

    public function getService(): string { return $this->service; }
    public function getAction(): string { return $this->action; }

    public function setMep(string $mep): self { $this->mep = $mep; return $this; }
    public function getMep(): string { return $this->mep; }

    public function setMepBinding(string $binding): self { $this->mepBinding = $binding; return $this; }
    public function getMepBinding(): string { return $this->mepBinding; }

    public function setMpc(string $mpc): self { $this->mpc = $mpc; return $this; }
    public function getMpc(): string { return $this->mpc; }

    // Security methods
    public function isSigningEnabled(): bool { return $this->signEnabled; }
    public function setSignCertificate(string $cert): self { $this->signCertificate = $cert; return $this; }
    public function getSignCertificate(): string { return $this->signCertificate; }

    public function isEncryptionEnabled(): bool { return $this->encryptEnabled; }
    public function setEncryptCertificate(string $cert): self { $this->encryptCertificate = $cert; return $this; }
    public function getEncryptCertificate(): string { return $this->encryptCertificate; }

    public function getSignAlgorithm(): string { return $this->signAlgorithm; }
    public function getEncryptAlgorithm(): string { return $this->encryptAlgorithm; }
    public function getKeyAgreement(): string { return $this->keyAgreement; }

    // Receipt methods
    public function shouldSendReceipt(): bool { return $this->sendReceipt; }
    public function shouldSignReceipt(): bool { return $this->receiptNonRepudiation; }
    public function getReceiptReplyPattern(): string { return $this->receiptReplyPattern; }
    public function setReceiptReplyPattern(string $pattern): self { $this->receiptReplyPattern = $pattern; return $this; }

    // Error handling
    public function shouldReportAsResponse(): bool { return $this->reportAsResponse; }
    public function shouldNotifyConsumerOnError(): bool { return $this->processErrorNotifyConsumer; }
    public function shouldNotifyProducerOnError(): bool { return $this->processErrorNotifyProducer; }
    public function shouldNotifyProducerOnMissingReceipt(): bool { return $this->missingReceiptNotifyProducer; }

    // Reliable Messaging
    public function isReceptionAwarenessEnabled(): bool { return $this->receptionAwarenessEnabled; }
    public function isRetryEnabled(): bool { return $this->retryEnabled; }
    public function getMaxRetries(): int { return $this->retryMaxRetries; }
    public function setMaxRetries(int $retries): self { $this->retryMaxRetries = $retries; return $this; }
    public function getRetryIntervalSeconds(): int { return $this->retryIntervalSeconds; }
    public function setRetryIntervalSeconds(int $seconds): self { $this->retryIntervalSeconds = $seconds; return $this; }

    public function isDuplicateDetectionEnabled(): bool { return $this->duplicateDetectionEnabled; }

    // Compression
    public function isCompressionEnabled(): bool { return $this->compressionEnabled; }
    public function setCompressionEnabled(bool $enabled): self { $this->compressionEnabled = $enabled; return $this; }
    public function getCompressionType(): string { return $this->compressionType; }

    // Four Corner Topology
    public function isFourCornerEnabled(): bool { return $this->fourCornerEnabled; }
    public function setFourCornerEnabled(bool $enabled): self { $this->fourCornerEnabled = $enabled; return $this; }

    public function getWssVersion(): string { return $this->wssVersion; }

    /**
     * Convert P-Mode to array for serialization/storage
     */
    public function toArray(): array
    {
        return [
            'initiator' => [
                'party' => $this->initiatorParty,
                'party_type' => $this->initiatorPartyType,
                'role' => $this->initiatorRole,
            ],
            'responder' => [
                'party' => $this->responderParty,
                'party_type' => $this->responderPartyType,
                'role' => $this->responderRole,
                'protocol_address' => $this->responderProtocolAddress,
            ],
            'business_info' => [
                'service' => $this->service,
                'action' => $this->action,
                'mep' => $this->mep,
                'mep_binding' => $this->mepBinding,
                'mpc' => $this->mpc,
            ],
            'security' => [
                'wss_version' => $this->wssVersion,
                'sign' => [
                    'enabled' => $this->signEnabled,
                    'certificate' => $this->signCertificate,
                    'algorithm' => $this->signAlgorithm,
                    'hash_algorithm' => $this->signHashAlgorithm,
                ],
                'encrypt' => [
                    'enabled' => $this->encryptEnabled,
                    'certificate' => $this->encryptCertificate,
                    'algorithm' => $this->encryptAlgorithm,
                    'key_agreement' => $this->keyAgreement,
                    'key_wrapping' => $this->keyWrapping,
                    'key_derivation' => $this->keyDerivation,
                    'key_derivation_prf' => $this->keyDerivationPrf,
                ],
            ],
            'receipts' => [
                'send_receipt' => $this->sendReceipt,
                'non_repudiation' => $this->receiptNonRepudiation,
                'reply_pattern' => $this->receiptReplyPattern,
            ],
            'error_handling' => [
                'report_as_response' => $this->reportAsResponse,
                'notify_consumer' => $this->processErrorNotifyConsumer,
                'notify_producer' => $this->processErrorNotifyProducer,
                'missing_receipt_notify_producer' => $this->missingReceiptNotifyProducer,
            ],
            'reliability' => [
                'reception_awareness' => $this->receptionAwarenessEnabled,
                'retry' => [
                    'enabled' => $this->retryEnabled,
                    'max_retries' => $this->retryMaxRetries,
                    'interval_seconds' => $this->retryIntervalSeconds,
                ],
                'duplicate_detection' => $this->duplicateDetectionEnabled,
            ],
            'compression' => [
                'enabled' => $this->compressionEnabled,
                'type' => $this->compressionType,
            ],
            'four_corner_topology' => $this->fourCornerEnabled,
        ];
    }
}
