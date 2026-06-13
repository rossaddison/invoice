<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;

final class InvCreditDeps
{
    public function __construct(
        public readonly CR $clientRepository,
        public readonly GR $gR,
        public readonly TRR $trR,
        public readonly UR $uR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {
    }
}
