<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\UserClient\UserClientRepository as ucR;
use App\Invoice\UserInv\UserInvRepository as uiR;

final class ClientIndexDeps
{
    public function __construct(
        public readonly ClientRepository $cR,
        public readonly cpR $cpR,
        public readonly iaR $iaR,
        public readonly iR $iR,
        public readonly ucR $ucR,
        public readonly uiR $uiR,
    ) {
    }
}
