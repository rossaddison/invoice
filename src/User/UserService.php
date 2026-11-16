<?php

declare(strict_types=1);

namespace App\User;

use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\AppConstants;
use App\Invoice\UserInv\UserInvRepository;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\User\CurrentUser;

final class UserService
{
    /**
     * Session key the login flow uses for the second factor: set to false when the password has been
     * accepted and the one-time code is still to be entered, to true once it has been.
     */
    public const string SESSION_TFA_VERIFIED = 'tfa_verified';

    /** @var array<int, bool> userinv.active by user id, read once per request (see isBlockedAsInactive()) */
    private array $blockedAsInactive = [];

    public function __construct(
        private readonly CurrentUser $currentUser,
        private readonly UserRepository $repository,
        private readonly AccessCheckerInterface $accessChecker,
        private readonly SessionInterface $session,
        private readonly UserInvRepository $userInvRepository,
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
        // The password step has already put the identity into the session (CurrentUser::login()), so
        // until the one-time code is entered that identity must hold no permission at all. Only an
        // explicit false means "code pending": a session that never went through the 2FA step (remember-me
        // cookie, OAuth, 2FA off) has no such value and is unaffected.
        if ($this->session->get(self::SESSION_TFA_VERIFIED) === false) {
            return false;
        }

        $userId = $this->resolveUserId();
        if (null === $userId || $this->isBlockedAsInactive($userId)) {
            return false;
        }

        return $this->accessChecker->userHasPermission(
            $userId,
            $permission
        );
    }

    /**
     * An account that is, or has been made, inactive (userinv.active = 0) holds no permission, even
     * if its session was opened while it was still active: the login only checks the flag once, so
     * an administrator deactivating an account would otherwise take effect only at the next login.
     * Administrators are exempt, exactly as at login, because they are never locked out by
     * activation. An account with no userinv row is left to the role check, as before.
     *
     * Read once per user per request: hasPermission() is called many times while a page is built.
     */
    private function isBlockedAsInactive(int $userId): bool
    {
        if (!array_key_exists($userId, $this->blockedAsInactive)) {
            $userInv = $this->userInvRepository->repoUserInvUserIdquery($userId);
            $this->blockedAsInactive[$userId] = null !== $userInv
                && !$userInv->getActive()
                && !$this->accessChecker->userHasPermission($userId, AppConstants::ROLE_ADMIN);
        }
        return $this->blockedAsInactive[$userId];
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
