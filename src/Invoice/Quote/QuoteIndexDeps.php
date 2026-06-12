<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    Client\ClientRepository as CR,
    Group\GroupRepository as GR,
    Quote\QuoteRepository as QR,
    QuoteAmount\QuoteAmountRepository as QAR,
    SalesOrder\SalesOrderRepository as SOR,
    UserClient\UserClientRepository as UCR,
};

final class QuoteIndexDeps
{
    public function __construct(
        public readonly QAR $qaR,
        public readonly QR $quoteRepo,
        public readonly CR $clientRepo,
        public readonly GR $groupRepo,
        public readonly SOR $soR,
        public readonly UCR $ucR,
    ) {}
}
