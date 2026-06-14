<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as qiaR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as qiaS;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as uR;

final class ProductLookupQuoteDeps
{
    public function __construct(
        public readonly ProductRepository $pR,
        public readonly trR $trR,
        public readonly uR $uR,
        public readonly qiaR $qiaR,
        public readonly qiaS $qiaS,
    ) {}
}
