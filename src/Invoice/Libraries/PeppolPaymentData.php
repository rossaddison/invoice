<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Ubl\PaymentMeans;
use App\Invoice\Ubl\PaymentTerms;

readonly class PeppolPaymentData
{
    public function __construct(
        public PaymentMeans $paymentMeans,
        // Nullable so PeppolHelper can omit the whole <cac:PaymentTerms>
        // wrapper (matching Invoice::xmlSerialize()'s own null check) when
        // there's no real terms text -- a PaymentTerms object constructed
        // with a null Note serializes as a childless, empty wrapper
        // element, which is just as much a Peppol validation failure
        // (PEPPOL-EN16931-R008) as the empty Note itself was.
        public ?PaymentTerms $paymentTerms,
    ) {
    }
}
