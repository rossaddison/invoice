<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;

final class QuoteEmailStage2RelationDeps
{
    public function __construct(
        public readonly QAR $qaR,
        public readonly QR $qR,
        public readonly SOR $soR,
    ) {
    }
}
