<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;

final class InvCreateCreditUserDeps
{
    public function __construct(
        public readonly UR $uR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {
    }
}
