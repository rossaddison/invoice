<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;

final class QuotePdfItemDeps
{
    public function __construct(
        public readonly QIR $qiR,
        public readonly QIAR $qiaR,
        public readonly ACQIR $acqiR,
        public readonly QTRR $qtrR,
    ) {
    }
}
