<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\WorldpayMerchant\WorldpayMerchant;

final class WorldpayWebhookContext
{
    public function __construct(
        public readonly Inv $invoice,
        public readonly InvAmount $invoiceAmountRecord,
        public readonly WorldpayMerchant $worldpayMerchant,
        public readonly string $eventType,
    ) {
    }
}
