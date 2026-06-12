<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    Client\ClientRepository as CR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    Quote\QuoteRepository as QR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};

final class QuoteEditCoreDeps
{
    public function __construct(
        public readonly QR $quoteRepo,
        public readonly IR $invRepo,
        public readonly CR $clientRepo,
        public readonly GR $groupRepo,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {}
}
