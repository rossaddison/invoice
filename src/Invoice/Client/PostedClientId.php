<?php

declare(strict_types=1);

namespace App\Invoice\Client;

/**
 * Which client an edit form is saving a record for.
 *
 * A quote, sales order or invoice belongs to the user assigned to its client: the guest menu, the emailed link and
 * the guest's own access checks all go by that owner. The edit forms let staff change the client, so the owner has
 * to be looked up for the client being POSTED, not the one the record had when the page was built.
 */
final class PostedClientId
{
    /**
     * The posted client when the form carries a usable one, otherwise the record's current client.
     *
     * @param array<array-key, mixed> $body what the form posted
     */
    public static function from(array $body, int $currentClientId): int
    {
        return isset($body['client_id']) && is_numeric($body['client_id']) && (int) $body['client_id'] > 0
            ? (int) $body['client_id']
            : $currentClientId;
    }
}
