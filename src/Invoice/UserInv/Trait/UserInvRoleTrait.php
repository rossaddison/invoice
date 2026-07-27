<?php

declare(strict_types=1);

namespace App\Invoice\UserInv\Trait;

use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\AppConstants;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\Invoice\UserInv\UserRbacLinkRepository;
use App\Invoice\Worker\WorkerRepository as wR;
use App\User\UserRepository as uR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

trait UserInvRoleTrait
{
    /**
     * Links an existing, unlinked Worker record to this user's login and
     * assigns the worker RBAC role — the counterpart to
     * assignObserverRole()/assignAccountantRole() above, but the worker
     * role additionally needs to know *which* Worker this user is (see
     * InvRepository::repoWorkerVisible() / Trait\Guest.php), so the picked
     * worker_id is required in the POST body rather than being implicit.
     */
    public function assignWorkerRole(
        Request $request,
        #[RouteArgument('user_id')] string $user_id,
        UserRbacLinkRepository $urlR,
        uiR $uiR,
        wR $wR,
        uR $uR,
    ): Response {
        $body = $request->getParsedBody() ?? [];
        /** @var string $worker_id */
        $worker_id = is_array($body) ? ($body['worker_id'] ?? '') : '';
        // repoUserInvUserIdquery() confirms the user actually exists before
        // findById() is called — that method assumes a persisted id and
        // isn't itself null-safe.
        $userInv = $user_id !== '' ? $uiR->repoUserInvUserIdquery((int) $user_id) : null;
        $worker = $worker_id !== '' ? $wR->repoWorkerquery((int) $worker_id) : null;
        if ($userInv !== null && $worker !== null && $worker->getUser() === null) {
            $user = $uR->findById((int) $user_id);
            $this->manager->revokeAll($user_id);
            $this->manager->assign(AppConstants::ROLE_WORKER, $user_id);
            $urlR->upsert($userInv->reqId(), (int) $user_id);
            $worker->setUser($user);
            $wR->save($worker);
            $this->flashMessage('info', $this->translator->translate('user.inv.role.worker.assigned'));
        } else {
            $this->flashMessage('warning', $this->translator->translate('user.inv.role.worker.unavailable'));
        }
        return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
    }
    public function assignObserverRole(
        #[RouteArgument('user_id')] string $user_id,
        UserRbacLinkRepository $urlR,
        uiR $uiR,
    ): Response {
        if ($user_id !== '') {
            $this->manager->revokeAll($user_id);
            $this->manager->assign(AppConstants::ROLE_OBSERVER, $user_id);
            $userInv = $uiR->repoUserInvUserIdquery((int) $user_id);
            if ($userInv !== null) {
                $urlR->upsert($userInv->reqId(), (int) $user_id);
            }
            $this->flashMessage('info', $this->translator->translate('user.inv.role.observer.assigned'));
        }
        return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
    }

    public function syncRbacLink(
        #[RouteArgument('user_id')] string $user_id,
        UserRbacLinkRepository $urlR,
        uiR $uiR,
    ): Response {
        if ($user_id !== '') {
            $roles = $this->manager->getRolesByUserId($user_id);
            if (empty($roles)) {
                $this->manager->assign(AppConstants::ROLE_OBSERVER, $user_id);
                $this->flashMessage('info', $this->translator->translate('user.inv.role.observer.assigned'));
            }
            $userInv = $uiR->repoUserInvUserIdquery((int) $user_id);
            if ($userInv !== null) {
                $urlR->upsert($userInv->reqId(), (int) $user_id);
            }
            $this->flashMessage('info', $this->translator->translate('user.inv.rbac.link.synced'));
        }
        return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
    }

    public function assignAccountantRole(
        #[RouteArgument('user_id')] string $user_id,
        UserRbacLinkRepository $urlR,
        uiR $uiR,
    ): Response {
        if ($user_id !== '') {
            $this->manager->revokeAll($user_id);
            $this->manager->assign(AppConstants::ROLE_ACCOUNTANT, $user_id);
            $userInv = $uiR->repoUserInvUserIdquery((int) $user_id);
            if ($userInv !== null) {
                $urlR->upsert($userInv->reqId(), (int) $user_id);
            }
            $this->flashMessage('info', $this->translator->translate('user.inv.role.accountant.assigned'));
            $this->flashMessage('info', $this->translator->translate('user.inv.role.accountant.default'));
        }
        return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
    }

    public function revokeAllRoles(
        #[RouteArgument('user_id')] string $user_id,
        UserRbacLinkRepository $urlR,
    ): Response {
        if ($user_id !== '') {
            $this->manager->revokeAll($user_id);
            $urlR->deleteByUserId((int) $user_id);
            $this->flashMessage('info', $this->translator->translate('user.inv.role.revoke.all'));
        }
        return $this->webService->getRedirectResponse(self::REDIRECT_USERINV_INDEX);
    }

    private function applyRolePolicyOnAdd(string $userId, string $type, UserInv $userinv, array $body): void
    {
        // non-admin user assigned guest type → observer role
        if ($userId != '1' && $type == '1') {
            $roles = $this->manager->getRolesByUserId($userId);
            if (!array_key_exists(AppConstants::ROLE_OBSERVER, $roles)) {
                $this->manager->assign(AppConstants::ROLE_OBSERVER, $userId);
                $this->flashMessage('info', $this->translator->translate('user.inv.role.all.new'));
            } else {
                $this->flashMessage('info', $this->translator->translate('user.inv.role.observer.assigned.already'));
            }
            $this->userinvService->saveUserInv($userinv, $body);
        }
        if ($userId != '1' && $type == '0') {
            $this->flashMessage('warning', $this->translator->translate('user.inv.type.cannot.allocate.administrator.type.to.non.administrator'));
        }
        // admin user assigned administrator type → admin role
        if ($userId == '1' && $type == '0') {
            $roles = $this->manager->getRolesByUserId($userId);
            if (!array_key_exists(AppConstants::ROLE_ADMIN, $roles)) {
                $this->manager->assign(AppConstants::ROLE_ADMIN, $userId);
                $this->flashMessage('info', $this->translator->translate('user.inv.role.administrator.assigned'));
            } else {
                $this->flashMessage('info', $this->translator->translate('user.inv.role.administrator.already.assigned'));
            }
            $this->userinvService->saveUserInv($userinv, $body);
        }
        if ($userId == '1' && $type == '1') {
            $this->flashMessage('warning', $this->translator->translate('user.inv.type.cannot.allocate.guest.type.to.administrator'));
        }
    }

    private function applyRolePolicyOnEdit(string $userId, string $type, UserInv $userinv, array $body): void
    {
        if ($userId != '1' && $type == '1') {
            $roles = $this->manager->getRolesByUserId($userId);
            if (!array_key_exists(AppConstants::ROLE_OBSERVER, $roles)) {
                $this->manager->assign(AppConstants::ROLE_OBSERVER, $userId);
                $this->flashMessage('info', $this->translator->translate('user.inv.role.all.new'));
            } else {
                $this->flashMessage('warning', $this->translator->translate('user.inv.role.observer.assigned.already'));
            }
            $this->userinvService->saveUserInv($userinv, $body);
        }
        if ($userId != '1' && $type == '0') {
            $this->flashMessage('warning', $this->translator->translate('user.inv.type.cannot.allocate.administrator.type.to.non.administrator'));
        }
        if ($userId == '1' && $type == '0') {
            $roles = $this->manager->getRolesByUserId($userId);
            if (!array_key_exists(AppConstants::ROLE_ADMIN, $roles)) {
                $this->manager->assign(AppConstants::ROLE_ADMIN, $userId);
                $this->flashMessage('info', $this->translator->translate('user.inv.role.administrator.assigned'));
            } else {
                $this->flashMessage('warning', $this->translator->translate('user.inv.role.administrator.already.assigned'));
            }
            $this->userinvService->saveUserInv($userinv, $body);
        }
        if ($userId == '1' && $type == '1') {
            $this->flashMessage('warning', $this->translator->translate('user.inv.type.cannot.allocate.guest.type.to.administrator'));
        }
    }
}
