<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Auth\TokenRepository as tR;
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\UserClient\UserClientRepository as ucR;

final class UserInvSignupDeps
{
    public function __construct(
        public readonly cR $cR,
        public readonly UserInvRepository $uiR,
        public readonly ucR $ucR,
        public readonly tR $tR,
    ) {
    }
}
