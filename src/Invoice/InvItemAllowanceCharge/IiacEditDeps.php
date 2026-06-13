<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\InvTaxRate\InvTaxRateRepository;

final class IiacEditDeps
{
    public function __construct(
        public readonly AllowanceChargeRepository $acR,
        public readonly InvItemAllowanceChargeRepository $aciiR,
        public readonly InvAmountRepository $iaR,
        public readonly InvItemAmountRepository $iiaR,
        public readonly InvTaxRateRepository $itrR,
    ) {}
}
