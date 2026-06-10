<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Setting\SettingRepository as sR;

final class QuoteRecalculator
{
    public function __construct(
        private readonly ACQR $acqR,
        private readonly QIR $qiR,
        private readonly QIAR $qiaR,
        private readonly QTRR $qtrR,
        private readonly QAR $qaR,
        private readonly QR $qR,
        private readonly sR $sR,
    ) {
    }

    public function recalculate(int $quoteId): void
    {
        (new NumberHelper($this->sR))->calculateQuote(
            $quoteId,
            $this->acqR,
            $this->qiR,
            $this->qiaR,
            $this->qtrR,
            $this->qaR,
            $this->qR,
        );
    }
}
