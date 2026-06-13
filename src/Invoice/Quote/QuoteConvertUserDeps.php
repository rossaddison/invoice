<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use App\User\UserRepository as UR;

final class QuoteConvertUserDeps
{
    public function __construct(
        public readonly UR $uR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {}
}
