<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeService;
use App\Invoice\InvCustom\InvCustomService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\Inv\InvService;

final class SoControllerInvDeps
{
    public function __construct(
        public readonly InvService $invService,
        public readonly InvAllowanceChargeService $invAllowanceChargeService,
        public readonly InvCustomService $invCustomService,
        public readonly InvItemService $invItemService,
        public readonly InvTaxRateService $invTaxRateService,
    ) {
    }
}
