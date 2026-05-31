<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Ubl\ContractDocumentReference;
use App\Invoice\Ubl\OrderReference;

readonly class PeppolInvoiceReferences
{
    public function __construct(
        public OrderReference $orderReference,
        public ?ContractDocumentReference $contractDocumentReference,
        public ?bool $isCopyIndicator,
        public ?string $supplierAssignedAccountID,
    ) {
    }
}
