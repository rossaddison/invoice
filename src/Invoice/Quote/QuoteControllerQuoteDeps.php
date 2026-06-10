<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeService;
use App\Invoice\QuoteAmount\QuoteAmountService;
use App\Invoice\QuoteCustom\QuoteCustomService;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteTaxRate\QuoteTaxRateService;

final class QuoteControllerQuoteDeps
{
    public function __construct(
        public readonly QuoteAllowanceChargeService $qacService,
        public readonly QuoteAmountService $quoteAmountService,
        public readonly QuoteCustomService $quoteCustomService,
        public readonly QuoteItemService $quoteItemService,
        public readonly QuoteService $quoteService,
        public readonly QuoteTaxRateService $quoteTaxRateService,
    ) {
    }
}
