<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

/**
 * Peppol BIS Advanced Ordering `cbc:LineStatusCode` -- the real UBL
 * codelist for a single `cac:OrderLine/cac:LineItem` response (see
 * docs.peppol.eu/poacc/upgrade-3/syntax/OrderResponse/, verified against
 * the real Peppol docs). Unlike the header-level OrderResponseCode, every
 * one of these 5 values is a real Seller decision on an individual line,
 * so all 5 are modelled here (not just the 3 most common).
 */
enum OrderResponseLineStatusCode: string
{
    case Added = '1';
    case Changed = '3';
    case Accepted = '5';
    case Rejected = '7';
    case AlreadyDelivered = '42';

    public function translationKey(): string
    {
        return match ($this) {
            self::Added            => 'salesorder.peppol.response.linestatus.added',
            self::Changed          => 'salesorder.peppol.response.linestatus.changed',
            self::Accepted         => 'salesorder.peppol.response.linestatus.accepted',
            self::Rejected         => 'salesorder.peppol.response.linestatus.rejected',
            self::AlreadyDelivered => 'salesorder.peppol.response.linestatus.alreadydelivered',
        };
    }
}
