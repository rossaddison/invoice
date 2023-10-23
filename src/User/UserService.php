<?php

declare(strict_types=1);

namespace App\User;

use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\User\CurrentUser;
use App\User\User;

final class UserService
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserRepository $repository,
        private AccessCheckerInterface $accessChecker
    ) {
    }
    
    /**
     * 
     * @return User|null  
     */
    public function getUser(): User|null
    {
        $userId = $this->currentUser->getId();
        if (null!==$userId) {
           return $this->repository->findById($userId); 
        } else {
           return null;
        } 
    }

    public function hasPermission(string $permission): bool
    {
        $userId = $this->currentUser->getId();

        return null !== $userId && $this->accessChecker->userHasPermission($userId, $permission);
    }
}
