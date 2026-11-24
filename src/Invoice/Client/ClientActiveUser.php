<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Infrastructure\Persistence\User\User;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\User\UserRepository;

/**
 * The one user who owns the records of a client: the user assigned to the client, provided that is exactly one user
 * and their account is active. Anything else (nobody assigned, several users, an inactive account) is no owner, so
 * a record cannot be given to that client.
 */
final readonly class ClientActiveUser
{
    public function __construct(
        private UserRepository $users,
        private UserClientRepository $userClients,
        private UserInvRepository $userInvs,
    ) {
    }

    public function forClient(int $clientId): ?User
    {
        $userClient = $this->userClients->repoUserquery($clientId);
        if (null === $userClient || 1 !== $this->userClients->repoUserqueryCount($clientId)) {
            return null;
        }
        $userId = $userClient->reqUserId();
        $userInv = $this->userInvs->repoUserInvUserIdquery($userId);
        if (null === $userInv || !$userInv->getActive()) {
            return null;
        }
        return $this->users->findById($userId);
    }
}
