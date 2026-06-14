<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\TokenRepository as tR;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\User\UserRepository as uR;

final class SignupDeps
{
    public function __construct(
        public readonly tR $tR,
        public readonly uiR $uiR,
        public readonly uR $uR,
    ) {}
}
