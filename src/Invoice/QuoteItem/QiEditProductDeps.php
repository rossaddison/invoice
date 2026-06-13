<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;

final class QiEditProductDeps
{
    public function __construct(
        public readonly QuoteItemRepository $qiR,
        public readonly TRR $trR,
        public readonly PR $pR,
        public readonly UR $uR,
        public readonly QR $qR,
        public readonly QIAS $qias,
        public readonly QIAR $qiar,
    ) {}
}
