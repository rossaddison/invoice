<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;

final class QuoteItemHtmxDependencies
{
    public function __construct(
        public readonly ACQIR $acqiR,
        public readonly ACQR $acqR,
        public readonly PIR $piR,
        public readonly PR $pR,
        public readonly QAR $qaR,
        public readonly QIAR $qiar,
        public readonly QuoteItemRepository $qiR,
        public readonly QR $qR,
        public readonly QTRR $qtrR,
        public readonly TaskR $taskR,
        public readonly TRR $trR,
        public readonly UR $uR,
    ) {
    }
}
