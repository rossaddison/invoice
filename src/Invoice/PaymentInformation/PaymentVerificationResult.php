<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

final class PaymentVerificationResult
{
    public function __construct(
        public readonly bool $paid,
        public readonly string $providerReference,
        public readonly string $message = '',
        // false when the gateway could not be asked or its answer could not be read, as opposed to a clear
        // "not paid": a caller may then treat the outcome as "try again later" rather than "unpaid"
        public readonly bool $conclusive = true,
    ) {
    }
}
