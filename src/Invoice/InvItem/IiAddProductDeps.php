<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;

final class IiAddProductDeps
{
    public function __construct(
        public readonly PR $pR,
        public readonly TRR $trR,
        public readonly IIAS $iias,
        public readonly IIAR $iiaR,
        public readonly SR $sR,
        public readonly UNR $uR,
    ) {}
}
