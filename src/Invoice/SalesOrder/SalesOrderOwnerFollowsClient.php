<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\Client\ClientActiveUser;
use App\Invoice\Client\PostedClientId;

/**
 * A sales order belongs to the user assigned to its client: an observer may only open an order whose user is
 * themselves and is linked to the order's client (SalesOrderRbacGuard::isObserver()). The staff edit form lets the
 * client be changed but never posts a user, so the order kept the old client's user and then belonged to nobody
 * who could open it.
 *
 * When staff move an order to another client it is given to that client's user. An order left on its client keeps
 * its owner.
 */
final readonly class SalesOrderOwnerFollowsClient
{
    public function __construct(private ClientActiveUser $owners)
    {
    }

    /**
     * @param array<array-key, mixed> $body what the staff edit form posted
     *
     * @return array<array-key, mixed>|null the body to save, with the new client's user in user_id when the client
     *     changes; null when the new client has no single active user to own the order, so it must not be saved
     */
    public function forStaffSave(SalesOrder $stored, array $body): ?array
    {
        $posted = PostedClientId::from($body, $stored->reqClientId());
        if ($posted === $stored->reqClientId()) {
            return $body;
        }
        $owner = $this->owners->forClient($posted);
        if (null === $owner) {
            return null;
        }
        $body['user_id'] = (string) $owner->reqId();

        return $body;
    }
}
