<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as uR;

final class ProductLookupInvDeps
{
    public function __construct(
        public readonly ProductRepository $pR,
        public readonly trR $trR,
        public readonly uR $uR,
        public readonly iiaR $iiaR,
        public readonly iiR $iiR,
    ) {}
}
