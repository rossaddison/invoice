<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserService;

final readonly class SalesOrderRbacGuard
{
    public function __construct(private UserService $userService) {}

    public function isObserver(SalesOrder $so, UCR $ucR, UIR $uiR): bool
    {
        $statusId = $so->getStatusId();
        if (null !== $statusId
            && $this->userService->hasPermission(Permissions::VIEW_INV)
            && !$this->userService->hasPermission(Permissions::EDIT_INV)
            && $statusId !== 1
            && (($soUserId = $so->reqUserId()) > 0)
            && ($soUserId === $this->userService->getUser()?->reqId())
            && ($ucR->repoUserClientqueryCount($soUserId, $so->reqClientId()) > 0)) {
            $userInv = $uiR->repoUserInvUserIdquery($soUserId);
            if (null !== $userInv && $userInv->getActive()) {
                return true;
            }
        }
        return false;
    }

    public function isAccountant(): bool
    {
        return $this->userService->hasPermission(Permissions::VIEW_INV)
            && $this->userService->hasPermission(Permissions::VIEW_PAYMENT)
            && $this->userService->hasPermission(Permissions::EDIT_PAYMENT);
    }

    public function isAdmin(): bool
    {
        return $this->userService->hasPermission(Permissions::VIEW_INV)
            && $this->userService->hasPermission(Permissions::EDIT_INV);
    }
}
