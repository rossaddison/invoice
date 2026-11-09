<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use DateTimeImmutable;

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
        /** The crew pulls the surrounding houses from this house. */
        public bool $pullPoint = false,
        /** The house number's numeric part, so the houses of a street can be put in number order. */
        public int $houseNumber = 0,
        /** When the houses around this pull point were last delegated; null if never (or not a pull point). */
        public ?DateTimeImmutable $delegatedAt = null,
    ) {
    }

    /**
     * The row as a field worker may see it: no client name, no phone and no amount (data
     * protection; workers never see prices), so a house is identified by its address alone. A row whose address was only the client's name
     * (no address on file) keeps no address at all rather than the name.
     */
    public function withoutClientDetails(): self
    {
        return new self(
            $this->address === $this->clientName ? '' : $this->address,
            $this->mapsUrl,
            '',
            null,
            $this->invNumber,
            null,
            $this->streetSortOrder,
            $this->invId,
            $this->sides,
            $this->pullPoint,
            $this->houseNumber,
            $this->delegatedAt,
        );
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
            $this->pullPoint,
            $this->houseNumber,
            $this->delegatedAt,
        );
    }
}
