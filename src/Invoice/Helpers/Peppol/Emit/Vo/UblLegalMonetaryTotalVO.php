<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

readonly class UblLegalMonetaryTotalVO
{
    public function __construct(
        public float  $lineExtensionAmount,
        public float  $taxExclusiveAmount,
        public float  $taxInclusiveAmount,
        public float  $payableAmount,
        public ?float $allowanceTotalAmount,
        public ?float $chargeTotalAmount,
        public ?float $prepaidAmount,
        public ?float $payableRoundingAmount,
    ) {}
}
