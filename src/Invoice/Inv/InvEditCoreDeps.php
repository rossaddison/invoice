<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;

final class InvEditCoreDeps
{
    public function __construct(
        public readonly InvRepository $invRepo,
        public readonly CR $clientRepo,
        public readonly GR $groupRepo,
        public readonly UR $userRepo,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {
    }
}
