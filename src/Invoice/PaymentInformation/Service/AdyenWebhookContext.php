<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;

final class AdyenWebhookContext
{
    public function __construct(
        public readonly Inv $invoice,
        public readonly InvAmount $invoiceAmountRecord,
        public readonly string $invoiceUrlKey,
        public readonly string $pspReference,
        public readonly bool $succeeded,
    ) {
    }
}
