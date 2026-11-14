<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

/**
 * What {@see RunHouseListParser} read from a pasted run: nothing is saved yet.
 * This is what the preview shows, and what the canonical run file
 * ({@see self::toCanonical()}) is made from.
 */
final readonly class ParsedRun
{
    /**
     * @param string $title the run's heading line as written
     * @param list<string> $headerNotes other lines before the first street
     * @param list<RunSegment> $segments in driving order
     * @param list<string> $warnings anything doubtful, for the preview
     */
    public function __construct(
        public string $title,
        public array $headerNotes,
        public array $segments,
        public array $warnings,
    ) {
    }

    public function houseCount(): int
    {
        $count = 0;
        foreach ($this->segments as $segment) {
            $count += count($segment->houses);
        }
        return $count;
    }

    /**
     * Distinct street names, in order of first appearance.
     *
     * @return list<string>
     */
    public function streets(): array
    {
        $names = [];
        foreach ($this->segments as $segment) {
            $names[$segment->street] = true;
        }
        return array_map('strval', array_keys($names));
    }

    /**
     * The one structured form a run is stored, exported and re-imported in:
     * every house once, with its visit sequence (the van's order), so a
     * re-import can be compared with what is stored.
     *
     * @return array{title: string, houses: list<array{sequence: int, street: string, postcode: string|null, number: string, flags: list<string>, note: string}>}
     */
    public function toCanonical(): array
    {
        $houses = [];
        $sequence = 0;
        foreach ($this->segments as $segment) {
            foreach ($segment->houses as $house) {
                $houses[] = [
                    'sequence' => ++$sequence,
                    'street' => $segment->street,
                    'postcode' => $segment->postcode,
                    'number' => $house->number,
                    'flags' => $house->flags,
                    'note' => $house->note,
                ];
            }
        }
        return ['title' => $this->title, 'houses' => $houses];
    }
}
