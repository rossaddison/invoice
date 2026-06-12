<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object carrying the parameters for an SMP endpoint lookup.
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4SmpQuery
{
    public function __construct(
        /** Peppol participant ID in "scheme:value" form, e.g. "0088:1234567890123" */
        public string $participantId,
        /**
         * OASIS document type identifier, e.g.
         * "busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::UBL-Invoice-2.1::2.1"
         */
        public string $documentTypeId,
        /** Peppol process identifier, e.g. "urn:fdc:peppol.eu:2017:poacc:billing:01:1.0" */
        public string $processId,
    ) {}
}
