<?php

declare(strict_types=1);

namespace App\Invoice\Libraries;

use App\Invoice\Ubl\LegalMonetaryTotal;

readonly class PeppolFinancialData
{
    public function __construct(
        public array $allowanceCharges,
        public array $taxAmounts,
        public array $taxSubtotal,
        public LegalMonetaryTotal $legalMonetaryTotal,
        public array $invoiceLines,
    ) {
    }
}
