<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\InvItemAmount\InvItemAmountService;
use App\Invoice\Product\ProductRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;

/**
 * Groups the five repositories needed to add InvItem rows from a product list.
 * Injected as a single DI parameter to keep action-method param counts within S107 limits.
 */
final class InvItemDeps
{
    public function __construct(
        public readonly InvItemAmountRepository $iiar,
        public readonly InvItemAmountService $iias,
        public readonly ProductRepository $pR,
        public readonly TaxRateRepository $trR,
        public readonly UnitRepository $unR,
    ) {}
}
