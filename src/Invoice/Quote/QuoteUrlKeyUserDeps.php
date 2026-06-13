<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};

final class QuoteUrlKeyUserDeps
{
    public function __construct(
        public readonly QTRR $qtrR,
        public readonly UIR $uiR,
        public readonly UCR $ucR,
    ) {}
}
