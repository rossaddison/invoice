<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAllowanceCharge;

use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteAmount\QuoteAmountRepository;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository;

final class QiacDeleteFinancialDeps
{
    public function __construct(
        public readonly QuoteAmountRepository $qaR,
        public readonly QuoteRepository $qR,
        public readonly QuoteTaxRateRepository $qtrR,
    ) {}
}
