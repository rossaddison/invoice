<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\PaymentMethod\PaymentMethodRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;

final class InvoiceIndexDeps
{
    public function __construct(
        public readonly TaxRateRepository $trR,
        public readonly UnitRepository $uR,
        public readonly FamilyRepository $fR,
        public readonly PaymentMethodRepository $pmR,
        public readonly ProductRepository $pR,
        public readonly ClientRepository $cR,
        public readonly GroupRepository $gR,
    ) {
    }
}
