<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    Client\ClientRepository as CR,
    Group\GroupRepository as GR,
    TaxRate\TaxRateRepository as TRR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};
use App\User\UserRepository as UR;

final class QuoteAddDeps
{
    public function __construct(
        public readonly CR $clientRepository,
        public readonly GR $gR,
        public readonly TRR $trR,
        public readonly UR $uR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {}
}
