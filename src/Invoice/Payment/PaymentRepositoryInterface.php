<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

interface PaymentRepositoryInterface
{
    public function repoLatestPaymentDateForInvquery(int $inv_id): ?string;
}
