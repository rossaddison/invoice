<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAllowanceCharge;

use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\QuoteAmount\QuoteAmountRepository;
use App\Invoice\QuoteItem\QuoteItemRepository;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository;

final class QiacAddDeps
{
    public function __construct(
        public readonly AllowanceChargeRepository $acR,
        public readonly QuoteItemAllowanceChargeRepository $acqiR,
        public readonly QuoteItemRepository $qiR,
        public readonly QuoteAmountRepository $qaR,
        public readonly QuoteItemAmountRepository $qiaR,
        public readonly QuoteTaxRateRepository $qtrR,
    ) {}
}
