<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use App\Invoice\Quote\QuoteRepository as qR;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as acqR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as qaR;
use App\Invoice\QuoteItem\QuoteItemRepository as qiR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as qiaR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as qiaS;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as qtrR;
use App\Invoice\TaxRate\TaxRateRepository as trR;

final class TaskSelectionQuoteDeps
{
    public function __construct(
        public readonly acqR $acqR,
        public readonly qaR $qaR,
        public readonly qiaR $qiaR,
        public readonly qiaS $qiaS,
        public readonly qiR $qiR,
        public readonly qR $qR,
        public readonly qtrR $qtrR,
        public readonly TaskRepository $taskR,
        public readonly trR $trR,
    ) {
    }
}
