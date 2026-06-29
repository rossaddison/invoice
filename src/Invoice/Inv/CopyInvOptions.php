<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Payment\PaymentService;

final class CopyInvOptions
{
    public function __construct(
        public readonly string $createdDate = '',
        public readonly string $note = '',
        public readonly bool $sameAmount = true,
        public readonly string $paymentDate = '',
        public readonly ?PaymentService $paymentService = null,
    ) {
    }
}
