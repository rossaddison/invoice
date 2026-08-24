<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use DateTimeImmutable;

/**
 * A parsed inbound Peppol BIS Order (T01,
 * urn:fdc:peppol.eu:poacc:trns:order:3). This app only ever receives
 * Order — it never issues one — so, unlike UblInvoiceData, the endpoint
 * captured here is the Buyer's (cac:BuyerCustomerParty), not a
 * Supplier's; the buyer is who this app needs to look up via
 * ClientPeppolRepositoryInterface and, eventually, who an
 * OrderResponseAdvanced gets sent back to.
 */
final readonly class UblOrderData
{
    /** @param UblOrderLineData[] $lines */
    public function __construct(
        public string            $orderNumber,
        public DateTimeImmutable $issueDate,
        public string            $currencyCode,
        public string            $buyerEndpointId,
        public string            $buyerEndpointSchemeId,
        public ?string           $note,
        public array             $lines,
    ) {}
}
