<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object carrying all parameters for SoapEnvelopeBuilder::build().
 *
 * @psalm-suppress UnusedClass
 */
final readonly class SoapEnvelopeParams
{
    public function __construct(
        /** RFC 2822 message ID, e.g. "msg-001@sender.com" */
        public string $messageId,
        /** Correlation ID linking related messages */
        public string $conversationId,
        /** Scheme:value, e.g. "0088:1234567890123" */
        public string $senderPartyId,
        /** Scheme:value, e.g. "0088:9876543210987" */
        public string $receiverPartyId,
        /** Peppol process profile URI */
        public string $service,
        /** Peppol document type identifier */
        public string $action,
        /** Well-formed UBL XML string */
        public string $payloadXml,
        /** MIME content-ID for the payload part, e.g. "invoice@sender.com" */
        public string $payloadContentId,
        /** ISO 8601 timestamp; null = current UTC time */
        public ?string $timestamp = null,
    ) {}
}
