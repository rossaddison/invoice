<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

/**
 * A stretch of one street in the order the van drives it. A street can come back later in the run
 * ("EXAMPLE CRESCENT (CONTINUES)"), which is a second segment of the same street.
 */
final readonly class RunSegment
{
    /**
     * @param string $street the street name, capitalised as a name ("Example Crescent")
     * @param string|null $postcode canonical, taken from the segment or the street's earlier segment
     * @param int|null $declaredCount the "(4)" written after the heading
     * @param bool $continues this is a later stretch of a street already seen
     * @param list<RunHouse> $houses in the order written
     * @param list<string> $notes driving or parking instructions and anything else that is not a house
     * @param int $line the 1-based line of the heading
     */
    public function __construct(
        public string $street,
        public ?string $postcode,
        public ?int $declaredCount,
        public bool $continues,
        public array $houses,
        public array $notes,
        public int $line,
    ) {
    }
}
