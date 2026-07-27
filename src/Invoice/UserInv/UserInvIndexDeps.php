<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\UserClient\UserClientRepository as ucR;
use App\Invoice\Worker\WorkerRepository as wR;

final class UserInvIndexDeps
{
    public function __construct(
        public readonly cR $cR,
        public readonly UserInvRepository $uiR,
        public readonly ucR $ucR,
        public readonly UserRbacLinkRepository $urlR,
        public readonly wR $wR,
    ) {
    }
}
