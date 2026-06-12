<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object carrying the inputs for As4MessageDispatcher::dispatch().
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4DispatchRequest
{
    public function __construct(
        /** Recipient's Peppol participant ID in "scheme:value" form, e.g. "0088:1234567890123" */
        public string $recipientPartyId,
        /**
         * OASIS document type identifier, e.g.
         * "busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::UBL-Invoice-2.1::2.1"
         */
        public string $documentTypeId,
        /** Peppol process identifier, e.g. "urn:fdc:peppol.eu:2017:poacc:billing:01:1.0" */
        public string $processId,
        /** Well-formed UBL XML string */
        public string $payloadXml,
        /** ebMS3 message ID; auto-generated when null */
        public ?string $messageId = null,
        /** Conversation ID correlating related messages; auto-generated when null */
        public ?string $conversationId = null,
        /** MIME content-ID for the payload attachment; auto-generated when null */
        public ?string $payloadContentId = null,
    ) {}
}
