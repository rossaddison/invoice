<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Helpers\InvRecalculator;
use App\Invoice\Payment\PaymentService;

final class InvEditPaymentDeps
{
    public function __construct(
        public readonly PaymentService $paymentService,
        public readonly InvRecalculator $invRecalculator,
    ) {
    }
}
