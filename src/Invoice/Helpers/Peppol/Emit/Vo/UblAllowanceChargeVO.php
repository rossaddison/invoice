<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

readonly class UblAllowanceChargeVO
{
    public function __construct(
        public bool    $chargeIndicator,
        public ?string $reasonCode,
        public ?string $reason,
        public float   $amount,
        public string  $amountCurrencyId,
        public ?float  $baseAmount,
        public ?float  $multiplierFactorNumeric,
        public ?string $taxCategoryId,
        public ?float  $taxCategoryPercent,
    ) {}
}
