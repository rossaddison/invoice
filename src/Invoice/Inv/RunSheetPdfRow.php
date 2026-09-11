<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

/**
 * One row of the `inv/index`-derived run sheet PDF -- see
 * Trait\RunSheetPdf's own docblock for how each field is resolved.
 */
final readonly class RunSheetPdfRow
{
    public function __construct(
        public string $address,
        public ?string $mapsUrl,
        public string $clientName,
        public ?string $phone,
        public string $invNumber,
        public ?float $balance,
        public ?int $streetSortOrder,
    ) {
    }
}
