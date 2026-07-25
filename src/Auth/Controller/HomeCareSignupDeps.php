<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\TokenRepository as tR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as csR;
use App\Invoice\HomeCarePendingSignup\HomeCarePendingSignupRepository as pendingR;
use App\Invoice\UserInv\UserInvRepository as uiR;
use App\User\UserRepository as uR;

final class HomeCareSignupDeps
{
    public function __construct(
        public readonly tR $tR,
        public readonly uiR $uiR,
        public readonly uR $uR,
        public readonly pendingR $pendingR,
        public readonly csR $csR,
    ) {
    }
}
