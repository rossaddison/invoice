<?php

declare(strict_types=1);

namespace App\Invoice\UserInv\Trait;

use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\AppConstants;

trait UserInvRoleTrait
{
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
