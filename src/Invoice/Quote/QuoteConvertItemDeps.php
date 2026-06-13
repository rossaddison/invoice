<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    Product\ProductRepository as PR,
    QuoteItem\QuoteItemRepository as QIR,
    QuoteTaxRate\QuoteTaxRateRepository as QTRR,
    Task\TaskRepository as TASKR,
    TaxRate\TaxRateRepository as TRR,
    Unit\UnitRepository as UNR,
};

final class QuoteConvertItemDeps
{
    public function __construct(
        public readonly PR $pR,
        public readonly QIR $qiR,
        public readonly QTRR $qtrR,
        public readonly TASKR $taskR,
        public readonly TRR $trR,
        public readonly UNR $unR,
    ) {}
}
