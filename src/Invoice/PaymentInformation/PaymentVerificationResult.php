<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

final class PaymentVerificationResult
{
    public function __construct(
        public readonly bool $paid,
        public readonly string $providerReference,
        public readonly string $message = '',
    ) {
    }
}
