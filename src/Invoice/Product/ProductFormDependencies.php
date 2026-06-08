<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\Family\FamilyRepository as fR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as uR;
use App\Invoice\UnitPeppol\UnitPeppolRepository as upR;

final class ProductFormDependencies
{
    public function __construct(
        public readonly fR $familyRepository,
        public readonly uR $unitRepository,
        public readonly trR $taxRateRepository,
        public readonly cvR $customValueRepository,
        public readonly cfR $customFieldRepository,
        public readonly upR $unitPeppolRepository,
    ) {
    }
}
