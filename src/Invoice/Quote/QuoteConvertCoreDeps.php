<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    Group\GroupRepository as GR,
    Quote\QuoteRepository as QR,
    QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR,
    QuoteAmount\QuoteAmountRepository as QAR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
};

final class QuoteConvertCoreDeps
{
    public function __construct(
        public readonly GR $gR,
        public readonly QR $qR,
        public readonly ACQR $acqR,
        public readonly ACQIR $acqiR,
        public readonly QAR $qaR,
        public readonly QCR $qcR,
    ) {}
}
