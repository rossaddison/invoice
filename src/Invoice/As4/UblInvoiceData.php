<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DateTimeImmutable;

final readonly class UblInvoiceData
{
    /** @param UblInvoiceLineData[] $lines */
    public function __construct(
        public string            $invoiceNumber,
        public DateTimeImmutable $issueDate,
        public DateTimeImmutable $dueDate,
        public string            $currencyCode,
        public string            $supplierEndpointId,
        public string            $supplierEndpointSchemeId,
        public float             $payableAmount,
        public ?string           $note,
        public ?string           $buyerReference,
        public array             $lines,
        public string            $documentType,
    ) {}
}
