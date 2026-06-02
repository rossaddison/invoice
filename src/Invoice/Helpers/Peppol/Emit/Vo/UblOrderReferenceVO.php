<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

readonly class UblOrderReferenceVO
{
    public function __construct(
        public string  $id,
        public ?string $salesOrderId,
    ) {}
}
