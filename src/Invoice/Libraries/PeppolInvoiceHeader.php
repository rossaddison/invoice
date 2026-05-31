<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

readonly class PeppolInvoiceHeader
{
    public function __construct(
        public ?string $profileID,
        public ?int $id,
        public PeppolInvoiceDates $dates,
        public ?string $note,
        public ?string $accountingCostCode,
        public ?string $buyerReference,
        public PeppolInvoiceReferences $references,
    ) {
    }
}
