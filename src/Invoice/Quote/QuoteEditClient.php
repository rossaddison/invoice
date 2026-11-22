<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

/**
 * Which client the quote edit form is saving the quote for.
 *
 * A quote belongs to the user assigned to its client: the guest menu, the emailed link and the guest's own checks all
 * go by that owner. The edit form lets staff change the client, so the owner has to be looked up for the client being
 * posted. It used to be looked up for the quote's old client, so a quote moved to another client kept the old client's
 * owner and disappeared for the new client's user.
 */
final class QuoteEditClient
{
    /**
     * The posted client when the form carries a usable one, otherwise the quote's current client.
     *
     * @param array<array-key, mixed> $body what the form posted
     */
    public static function beingSaved(array $body, int $currentClientId): int
    {
        return isset($body['client_id']) && is_numeric($body['client_id']) && (int) $body['client_id'] > 0
            ? (int) $body['client_id']
            : $currentClientId;
    }
}
