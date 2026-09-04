<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * One cac:OrderLine/cac:LineItem from an inbound Peppol BIS Order (T01).
 * $lineId is the buyer's own line identifier — kept so a later
 * OrderResponseAdvanced (see docs.peppol.eu/poacc/upgrade-3/syntax/OrderResponse/)
 * can reference exactly the line it's responding to; this app never
 * invents its own line numbering for an imported order.
 */
final readonly class UblOrderLineData
{
    public function __construct(
        public string $lineId,
        public string $name,
        public string $description,
        public float  $quantity,
        public string $unitCode,
        public float  $unitPrice,
        public float  $lineExtensionAmount,
    ) {
    }
}
