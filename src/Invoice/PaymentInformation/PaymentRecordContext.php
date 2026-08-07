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
        public readonly ?string $provider_reference = null,
        public readonly PaymentRecordChannel $channel = PaymentRecordChannel::Webhook,
        /**
         * A second provider-side reference, for gateways where one string
         * genuinely can't cover both the webhook-lookup identifier and the
         * refund-capable identifier (Square: order_id vs. payment_id — see
         * SquareMerchant). null for every gateway where provider_reference
         * alone is sufficient for both purposes. Deliberately generic, not
         * Square-specific naming — reusable the next time a gateway needs
         * its own per-provider entity for the same reason.
         */
        public readonly ?string $secondary_provider_reference = null,
    ) {
    }
}
