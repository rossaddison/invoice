<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\UserClient\UserClientRepository as ucR;

final class UserInvIndexDeps
{
    public function __construct(
        public readonly cR $cR,
        public readonly UserInvRepository $uiR,
        public readonly ucR $ucR,
    ) {
    }
}
