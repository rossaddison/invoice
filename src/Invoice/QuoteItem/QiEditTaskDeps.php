<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;

final class QiEditTaskDeps
{
    public function __construct(
        public readonly QuoteItemRepository $qiR,
        public readonly TRR $trR,
        public readonly TaskR $taskR,
        public readonly QIAS $qias,
        public readonly QIAR $qiar,
    ) {}
}
