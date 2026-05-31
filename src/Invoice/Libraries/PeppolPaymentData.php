<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Ubl\PaymentMeans;
use App\Invoice\Ubl\PaymentTerms;

readonly class PeppolPaymentData
{
    public function __construct(
        public PaymentMeans $paymentMeans,
        public PaymentTerms $paymentTerms,
    ) {
    }
}
