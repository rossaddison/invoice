<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

/**
 * Whether a client may be assigned to a user. A client has exactly one user (user_client.client_id is unique), and
 * that user owns its quotes, sales orders and invoices. The assign dropdown hides clients that already have a user,
 * but a posted client id is not limited to what the dropdown showed, so the rule is checked on the server too.
 */
final readonly class UserClientAssignmentRule
{
    public const string ALREADY_ASSIGNED_TO_THIS_USER = 'client.already.exists';
    public const string ALREADY_HAS_ANOTHER_USER = 'client.already.has.user';

    public function __construct(private UserClientRepository $userClients)
    {
    }

    /**
     * The translation key saying why the client cannot be assigned to the user, or null when it can be.
     */
    public function refusalFor(int $userId, int $clientId): ?string
    {
        if ($this->userClients->repoUserClientqueryCount($userId, $clientId) > 0) {
            return self::ALREADY_ASSIGNED_TO_THIS_USER;
        }
        if ($this->userClients->repoUserqueryCount($clientId) > 0) {
            return self::ALREADY_HAS_ANOTHER_USER;
        }

        return null;
    }
}
