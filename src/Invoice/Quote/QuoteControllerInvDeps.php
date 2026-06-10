<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvCustom\InvCustomService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\Inv\InvService;

final class QuoteControllerInvDeps
{
    public function __construct(
        public readonly InvAllowanceChargeService $invAllowanceChargeService,
        public readonly InvAmountService $invAmountService,
        public readonly InvService $invService,
        public readonly InvCustomService $invCustomService,
        public readonly InvItemService $invItemService,
        public readonly InvTaxRateService $invTaxRateService,
    ) {
    }
}
