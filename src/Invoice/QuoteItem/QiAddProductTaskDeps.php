<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;
use Yiisoft\Translator\TranslatorInterface as Translator;

final class QiAddProductTaskDeps
{
    public function __construct(
        public readonly PR $pr,
        public readonly TaskR $taskR,
        public readonly QIAR $qiar,
        public readonly QIAS $qias,
        public readonly UR $uR,
        public readonly TRR $trr,
        public readonly Translator $translator,
    ) {}
}
