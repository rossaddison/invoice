<?php

declare(strict_types=1);

namespace App\Invoice\As4;

final readonly class UblInvoiceLineData
{
    public function __construct(
        public string $name,
        public string $description,
        public float  $quantity,
        public string $unitCode,
        public float  $unitPrice,
        public float  $lineExtensionAmount,
        public string $peppolPoItemId,
        public string $peppolPoLineId,
    ) {}
}
