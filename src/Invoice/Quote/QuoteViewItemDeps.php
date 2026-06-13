<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    Product\ProductRepository as PR,
    QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR,
    QuoteCustom\QuoteCustomRepository as QCR,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR,
    QuoteItemAmount\QuoteItemAmountRepository as QIAR,
};

final class QuoteViewItemDeps
{
    public function __construct(
        public readonly QIR $qiR,
        public readonly QIAR $qiaR,
        public readonly ACQR $acqR,
        public readonly ACQIR $acqiR,
        public readonly QCR $qcR,
        public readonly PR $pR,
    ) {}
}
