<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\{
    Contract\ContractRepository as ContractRepo,
    DeliveryLocation\DeliveryLocationRepository as DLR,
};

final class QuoteEditLocationDeps
{
    public function __construct(
        public readonly ContractRepo $contractRepo,
        public readonly DLR $delRepo,
    ) {}
}
