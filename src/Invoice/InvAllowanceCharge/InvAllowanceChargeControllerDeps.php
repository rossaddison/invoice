<?php

declare(strict_types=1);

namespace App\Invoice\InvAllowanceCharge;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeService;
use App\Service\WebControllerService;

final class InvAllowanceChargeControllerDeps
{
    public function __construct(
        public readonly WebControllerService $webService,
        public readonly InvAllowanceChargeService $invallowancechargeService,
    ) {
    }
}
