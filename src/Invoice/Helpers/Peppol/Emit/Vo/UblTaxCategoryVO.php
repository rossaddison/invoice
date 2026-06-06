<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

readonly class UblTaxCategoryVO
{
    public function __construct(
        public string  $id,
        public ?float  $percent,
        public string  $taxSchemeId,
        public ?string $taxExemptionReasonCode,
        public ?string $taxExemptionReason,
    ) {}
}
