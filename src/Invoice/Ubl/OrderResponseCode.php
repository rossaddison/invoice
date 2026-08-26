<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

/**
 * Peppol BIS Advanced Ordering `cbc:OrderResponseCode` — the Seller's reply
 * to an inbound Order, sent back as an OrderResponseAdvanced document (see
 * docs.peppol.eu/poacc/upgrade-3/syntax/OrderResponse/, verified against the
 * real Peppol docs). This app only ever plays Seller for Ordering (see
 * App\Invoice\As4\As4OrderImportService's own docblock), so this enum only
 * needs to cover the codes a Seller can send, not every code in the wider
 * UNCL1225-derived list.
 */
enum OrderResponseCode: string
{
    /** Order received, response deferred -- not yet processed. */
    case Acknowledged = 'AB';
    /** Accepted as a whole, no changes. */
    case Accepted = 'AP';
    /** Rejected as a whole. */
    case Rejected = 'RE';
    /** Accepted, but with changes (quantities, prices, dates, ...). */
    case AcceptedWithChanges = 'CA';

    /**
     * Derives the header-level code from a set of per-line
     * OrderResponseLineStatusCode decisions: all Accepted -> Accepted; all
     * Rejected -> Rejected; anything else (mixed, or containing
     * Changed/Added/AlreadyDelivered) -> AcceptedWithChanges. Acknowledged
     * (AB) is never derived -- it only ever applies to the whole-order
     * "haven't decided any line yet" shortcut, never to a real per-line
     * response.
     */
    public static function deriveFromLineStatusCodes(OrderResponseLineStatusCode ...$codes): self
    {
        if ($codes === []) {
            return self::AcceptedWithChanges;
        }
        $allAccepted = true;
        $allRejected = true;
        foreach ($codes as $code) {
            $allAccepted = $allAccepted && $code === OrderResponseLineStatusCode::Accepted;
            $allRejected = $allRejected && $code === OrderResponseLineStatusCode::Rejected;
        }
        return match (true) {
            $allAccepted => self::Accepted,
            $allRejected => self::Rejected,
            default       => self::AcceptedWithChanges,
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::Acknowledged       => 'salesorder.peppol.response.ab',
            self::Accepted           => 'salesorder.peppol.response.ap',
            self::Rejected           => 'salesorder.peppol.response.re',
            self::AcceptedWithChanges => 'salesorder.peppol.response.ca',
        };
    }
}
