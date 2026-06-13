<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvTaxRate\InvTaxRateRepository;
use App\Invoice\Payment\PaymentRepository;

final class IiacDeleteFinancialDeps
{
    public function __construct(
        public readonly InvAmountRepository $iaR,
        public readonly InvRepository $iR,
        public readonly InvTaxRateRepository $itrR,
        public readonly PaymentRepository $pymR,
    ) {}
}
