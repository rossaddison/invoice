<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAllowanceCharge;

use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository;
use App\Invoice\QuoteItem\QuoteItemRepository;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository;

final class QiacDeleteSubEntityDeps
{
    public function __construct(
        public readonly QuoteItemRepository $qiR,
        public readonly QuoteItemAmountRepository $qiaR,
        public readonly QuoteAllowanceChargeRepository $acqR,
        public readonly QuoteItemAllowanceChargeRepository $acqiR,
    ) {}
}
