<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

readonly class UblTaxTotalVO
{
    /**
     * @param UblTaxSubtotalVO[] $taxSubtotals
     */
    public function __construct(
        public float  $taxAmount,
        public string $taxAmountCurrencyId,
        public array  $taxSubtotals,
    ) {}
}
