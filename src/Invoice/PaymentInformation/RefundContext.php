<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Payment\Payment;

final class RefundContext
{
    public function __construct(
        public readonly Payment $payment,
        public readonly string $driver,
        public readonly string $reference,
    ) {
    }
}
