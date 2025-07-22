<?php

declare(strict_types=1);

namespace App\Auth;

use App\User\UserRepository;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\User\CurrentUser;

final readonly class AuthService
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserRepository $userRepository,
        private IdentityRepository $identityRepository,
    ) {
    }

    public function login(string $login, string $password): bool
    {
        $user = $this->userRepository->findByLoginWithAuthIdentity($login);
        /*
         * Use Password Hashing to validate the password
         */
        if (null === $user || !$user->validatePassword($password)) {
            return false;
        }

        return $this->currentUser->login($user->getIdentity());
    }

    public function oauthLogin(string $login): bool
    {
        $user = $this->userRepository->findByLoginWithAuthIdentity($login);

        if (null === $user) {
            return false;
        }

        return $this->currentUser->login($user->getIdentity());
    }

    /**
     * @throws \Throwable
     */
    public function logout(): bool
    {
        $identity = $this->currentUser->getIdentity();

        if ($identity instanceof Identity) {
            $identity->regenerateCookieLoginKey();
            $this->identityRepository->save($identity);
        }

        return $this->currentUser->logout();
    }

    public function getIdentity(): IdentityInterface
    {
        return $this->currentUser->getIdentity();
    }

    public function isGuest(): bool
    {
        return $this->currentUser->isGuest();
    }
}
