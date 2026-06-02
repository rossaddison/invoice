<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

readonly class UblTaxSubtotalVO
{
    public function __construct(
        public float           $taxableAmount,
        public float           $taxAmount,
        public UblTaxCategoryVO $taxCategory,
    ) {}
}
