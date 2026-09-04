<?php

declare(strict_types=1);

namespace App\User;

use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\User\User;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\User\CurrentUser;

final readonly class UserService
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserRepository $repository,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        $userId = $this->resolveUserId();
        if (null !== $userId) {
            return $this->repository->findById($userId);
        }
        return null;
    }

    public function hasPermission(string $permission): bool
    {
        $userId = $this->resolveUserId();

        return null !== $userId && $this->accessChecker->userHasPermission(
            $userId,
            $permission
        );
    }

    /**
     * CurrentUser::getId() (via IdentityInterface::getId()) returns the
     * identity table's own row id, not the user's — Identity::getUserId()
     * does. Those two frequently diverge (see
     * docs/IDENTITY_VS_USER_ID_AUTH_FIX_JULY_2026.md), which silently broke
     * every permission check and getUser() call for any account where they
     * don't coincidentally match — confirmed live via runtime/logs/app.log
     * showing 'Access denied' checked against the wrong id.
     */
    private function resolveUserId(): ?int
    {
        $identity = $this->currentUser->getIdentity();
        return $identity instanceof Identity ? $identity->getUserId() : null;
    }
}
