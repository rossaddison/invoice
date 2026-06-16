<?php

declare(strict_types=1);

namespace App\Invoice\As4;

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
 *
 * Detailed configuration is delegated to five sub-objects:
 * getParties(), getSecurity(), getReliability(), getReceiptConfig(), getErrorConfig().
 */
class PMode
{
    private string $mep = As4Constants::MEP_ONE_WAY;
    private string $mepBinding = As4Constants::MEPBINDING_PUSH;
    private string $mpc = As4Constants::DEFAULT_MPC;
    private bool $compressionEnabled = true;
    private string $compressionType = As4Constants::COMPRESSION_TYPE;
    private bool $fourCornerEnabled = false;

    private PModeParties $parties;
    private PModeSecurity $security;
    private PModeReliability $reliability;
    private PModeReceiptConfig $receiptConfig;
    private PModeErrorConfig $errorConfig;

    public function __construct(
        string $initiatorParty,
        string $responderParty,
        string $responderProtocolAddress,
        private string $service,
        private string $action,
    ) {
        $this->parties       = new PModeParties($initiatorParty, $responderParty, $responderProtocolAddress);
        $this->security      = new PModeSecurity();
        $this->reliability   = new PModeReliability();
        $this->receiptConfig = new PModeReceiptConfig();
        $this->errorConfig   = new PModeErrorConfig();
    }

    public function getParties(): PModeParties { return $this->parties; }
    public function getSecurity(): PModeSecurity { return $this->security; }
    public function getReliability(): PModeReliability { return $this->reliability; }
    public function getReceiptConfig(): PModeReceiptConfig { return $this->receiptConfig; }
    public function getErrorConfig(): PModeErrorConfig { return $this->errorConfig; }

    public function getService(): string { return $this->service; }
    public function getAction(): string { return $this->action; }

    public function getMep(): string { return $this->mep; }
    public function setMep(string $mep): self { $this->mep = $mep; return $this; }

    public function getMepBinding(): string { return $this->mepBinding; }
    public function setMepBinding(string $binding): self { $this->mepBinding = $binding; return $this; }

    public function getMpc(): string { return $this->mpc; }
    public function setMpc(string $mpc): self { $this->mpc = $mpc; return $this; }

    public function isCompressionEnabled(): bool { return $this->compressionEnabled; }
    public function setCompressionEnabled(bool $enabled): self { $this->compressionEnabled = $enabled; return $this; }
    public function getCompressionType(): string { return $this->compressionType; }

    public function isFourCornerEnabled(): bool { return $this->fourCornerEnabled; }
    public function setFourCornerEnabled(bool $enabled): self { $this->fourCornerEnabled = $enabled; return $this; }

    /**
     * Convert P-Mode to array for serialization/storage.
     */
    public function toArray(): array
    {
        $p = $this->parties;
        $s = $this->security;
        $r = $this->reliability;

        return [
            'initiator' => [
                'party'      => $p->getInitiatorParty(),
                'party_type' => $p->getInitiatorPartyType(),
                'role'       => $p->getInitiatorRole(),
            ],
            'responder' => [
                'party'            => $p->getResponderParty(),
                'party_type'       => $p->getResponderPartyType(),
                'role'             => $p->getResponderRole(),
                'protocol_address' => $p->getResponderProtocolAddress(),
            ],
            'business_info' => [
                'service'     => $this->service,
                'action'      => $this->action,
                'mep'         => $this->mep,
                'mep_binding' => $this->mepBinding,
                'mpc'         => $this->mpc,
            ],
            'security' => [
                'wss_version' => $s->getWssVersion(),
                'sign'        => [
                    'enabled'        => $s->isSigningEnabled(),
                    'certificate'    => $s->getSignCertificate(),
                    'algorithm'      => $s->getSignAlgorithm(),
                    'hash_algorithm' => $s->getSignHashAlgorithm(),
                ],
                'encrypt' => [
                    'enabled'            => $s->isEncryptionEnabled(),
                    'certificate'        => $s->getEncryptCertificate(),
                    'algorithm'          => $s->getEncryptAlgorithm(),
                    'key_agreement'      => $s->getKeyAgreement(),
                    'key_wrapping'       => $s->getKeyWrapping(),
                    'key_derivation'     => $s->getKeyDerivation(),
                    'key_derivation_prf' => $s->getKeyDerivationPrf(),
                ],
            ],
            'receipts' => [
                'send_receipt'    => $this->receiptConfig->shouldSendReceipt(),
                'non_repudiation' => $this->receiptConfig->shouldSignReceipt(),
                'reply_pattern'   => $this->receiptConfig->getReceiptReplyPattern(),
            ],
            'error_handling' => [
                'report_as_response'              => $this->errorConfig->shouldReportAsResponse(),
                'notify_consumer'                 => $this->errorConfig->shouldNotifyConsumerOnError(),
                'notify_producer'                 => $this->errorConfig->shouldNotifyProducerOnError(),
                'missing_receipt_notify_producer' => $this->errorConfig->shouldNotifyProducerOnMissingReceipt(),
            ],
            'reliability' => [
                'reception_awareness' => $r->isReceptionAwarenessEnabled(),
                'retry'               => [
                    'enabled'          => $r->isRetryEnabled(),
                    'max_retries'      => $r->getMaxRetries(),
                    'interval_seconds' => $r->getRetryIntervalSeconds(),
                ],
                'duplicate_detection' => $r->isDuplicateDetectionEnabled(),
            ],
            'compression' => [
                'enabled' => $this->compressionEnabled,
                'type'    => $this->compressionType,
            ],
            'four_corner_topology' => $this->fourCornerEnabled,
        ];
    }
}
