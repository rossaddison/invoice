<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    CustomField\CustomFieldRepository as CFR,
    Quote\QuoteRepository as QR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
};

final class QuoteUrlKeyRepoDeps
{
    public function __construct(
        public readonly CFR $cfR,
        public readonly QAR $qaR,
        public readonly QIR $qiR,
        public readonly QIAR $qiaR,
        public readonly ACQIR $acqiR,
        public readonly QR $qR,
    ) {}
}
