<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

final class PaymentRefundResult
{
    public function __construct(
        public readonly bool $refunded,
        public readonly string $providerReference,
        public readonly string $message = '',
    ) {
    }
}
