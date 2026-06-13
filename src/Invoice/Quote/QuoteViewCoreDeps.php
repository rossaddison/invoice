<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    Quote\QuoteRepository as QR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    SalesOrder\SalesOrderRepository as SOR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR,
};

final class QuoteViewCoreDeps
{
    public function __construct(
        public readonly QR $qR,
        public readonly QAR $qaR,
        public readonly QTRR $qtrR,
        public readonly SOR $soR,
        public readonly UCR $ucR,
        public readonly UIR $uiR,
    ) {}
}
