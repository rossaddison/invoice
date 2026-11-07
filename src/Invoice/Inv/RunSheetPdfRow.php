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
        public int $invId = 0,
        /** The house's per-side allocations as one line ("Front: Dave; Back: Sam"); '' for none. */
        public string $sides = '',
    ) {
    }

    public function withSides(string $sides): self
    {
        return new self(
            $this->address,
            $this->mapsUrl,
            $this->clientName,
            $this->phone,
            $this->invNumber,
            $this->balance,
            $this->streetSortOrder,
            $this->invId,
            $sides,
        );
    }
}
