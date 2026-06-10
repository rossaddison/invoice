<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

final class PaymentRecordContext
{
    public function __construct(
        public readonly string $reference,
        public readonly string $invoice_id,
        public readonly float $balance,
        public readonly int $invoice_payment_method,
        public readonly string $invoice_number,
        public readonly string $driver,
        public readonly string $d,
        public readonly string $invoice_url_key,
        public readonly bool $response,
        public readonly array $sandbox_url_array,
    ) {
    }
}
