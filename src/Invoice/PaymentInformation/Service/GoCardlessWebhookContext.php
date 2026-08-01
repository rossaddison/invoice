<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;

final class GoCardlessWebhookContext
{
    public function __construct(
        public readonly Inv $invoice,
        public readonly InvAmount $invoiceAmountRecord,
        public readonly string $invoiceUrlKey,
        public readonly string $paymentId,
        public readonly bool $succeeded,
    ) {
    }
}
