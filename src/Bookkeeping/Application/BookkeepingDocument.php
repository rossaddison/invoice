<?php

declare(strict_types=1);

namespace App\Bookkeeping\Application;

use DateTimeImmutable;

/**
 * What a document-oriented bookkeeping provider (one that wants a customer
 * and an invoice, not just journal lines) needs to know about the invoice a
 * BookkeepingTransaction came from.
 */
final class BookkeepingDocument
{
    public function __construct(
        public readonly string $customerReference,
        public readonly string $customerName,
        public readonly string $customerAddress,
        public readonly string $documentNumber,
        public readonly DateTimeImmutable $dueDate,
    ) {
    }
}
