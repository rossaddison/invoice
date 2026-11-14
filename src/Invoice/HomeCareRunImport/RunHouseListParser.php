<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

use App\Invoice\HomeCareSchedule\UkPostcode;

/**
 * Reads a run as it is kept in the lead's document (pasted text),
 * not a tidy format
 */
final readonly class RunHouseListParser
{
    /** A word that makes an all-capitals line a street rather than an instruction. */
    private const array STREET_WORDS = [
        'ROAD', 'RD', 'STREET', 'ST', 'AVENUE', 'AVE', 'DRIVE', 'DR', 'CRESCENT',
        'CRES', 'GARDENS', 'GDNS', 'WYND', 'VIEW', 'PLACE', 'PL', 'LANE', 'LN',
        'COURT', 'CT', 'TERRACE', 'TER', 'WAY', 'CLOSE', 'GROVE', 'PARK',
        'GREEN', 'BRAE', 'LOAN', 'ROW', 'SQUARE', 'HILL', 'RISE', 'WALK', 'GATE',
        'BANK', 'PATH', 'CIRCLE', 'BOULEVARD', 'MEWS', 'VALE',
    ];

    private const string POSTCODE_SEARCH = '/\b([A-Z]{1,2}\d[A-Z\d]?)\s*(\d[A-Z]{2})\b/';

    public function parse(string $text): ParsedRun
    {
        $title = '';
        $headerNotes = [];
        /** @var list<array{street: string, postcode: string|null, declared: int|null, continues: bool, houses: list<RunHouse>, notes: list<string>, line: int}> $segments */
        $segments = [];
        $current = null;

        foreach (preg_split('/\R/u', $text) ?: [] as $index => $raw) {
            $lineNo = $index + 1;
            $line = $this->cleanLine($raw);
            if ('' === $line) {
                continue;
            }
            if ('' === $title) {
                $title = $line;
                continue;
            }
            $heading = $this->heading($line);
            if (null !== $heading) {
                $current = count($segments);
                $segments[] = $this->newSegment($heading, $segments, $lineNo);
                continue;
            }
            if (null === $current) {
                // "11 PARKVILLE BACKS HAVE BEEN MISSED" before the first street is an instruction
                $headerNotes[] = $line;
                continue;
            }
            $house = $this->house($line, $lineNo);
            if (null !== $house) {
                $segments[$current]['houses'][] = $house;
            } else {
                $segments[$current]['notes'][] = $line;
            }
        }

        return $this->built($title, $headerNotes, $segments);
    }

    /**
     * The line with non-breaking spaces and byte-order marks turned to spaces and trimmed; empty
     * for a blank line or a line of only rules and dots.
     */
    private function cleanLine(string $raw): string
    {
        $line = trim(str_replace(["\u{00A0}", "\u{FEFF}"], ' ', $raw));
        return 1 === preg_match('/^[\s.\-_=…~*]+$/u', $line) ? '' : $line;
    }

    /**
     * @param list<string> $headerNotes
     * @param array<int, array{street: string, postcode: string|null, declared: int|null, continues: bool, houses: list<RunHouse>, notes: list<string>, line: int}> $segments
     */
    private function built(string $title, array $headerNotes, array $segments): ParsedRun
    {
        $warnings = [];
        $built = [];
        foreach ($segments as $segment) {
            $found = count($segment['houses']);
            if (null !== $segment['declared'] &&
                    !$segment['continues'] && $segment['declared'] !== $found) {
                $warnings[] = sprintf(
                    'Line %d: %s says (%d) but %d %s found.',
                    $segment['line'],
                    $segment['street'],
                    $segment['declared'],
                    $found,
                    1 === $found ? 'house was' : 'houses were'
                );
            }
            $built[] = new RunSegment(
                $segment['street'],
                $segment['postcode'],
                $segment['declared'],
                $segment['continues'],
                $segment['houses'],
                $segment['notes'],
                $segment['line'],
            );
        }
        return new ParsedRun($title, $headerNotes, $built, $warnings);
    }

    /**
     * @param array{name: string, postcode: string|null, declared: int|null, continues: bool} $heading
     * @param array<int, array{street: string, postcode: string|null, declared: int|null, continues: bool, houses: list<RunHouse>, notes: list<string>, line: int}> $earlier
     * @return array{street: string, postcode: string|null, declared: int|null, continues: bool, houses: list<RunHouse>, notes: list<string>, line: int}
     */
    private function newSegment(array $heading, array $earlier, int $lineNo): array
    {
        $name = $heading['name'];
        $postcode = $heading['postcode'];
        $continues = $heading['continues'];
        foreach ($earlier as $segment) {
            $same = 0 === strcasecmp($segment['street'], $name);
            // "EXAMPLE CONTINUES" continues "Example Crescent"
            $shortForm = $heading['continues'] && str_starts_with(strtolower($segment['street']), strtolower($name));
            if ($same || $shortForm) {
                $name = $segment['street'];
                $postcode ??= $segment['postcode'];
                $continues = true;
                break;
            }
        }
        return [
            'street' => $name,
            'postcode' => $postcode,
            'declared' => $heading['declared'],
            'continues' => $continues,
            'houses' => [],
            'notes' => [],
            'line' => $lineNo,
        ];
    }

    /**
     * @return array{name: string, postcode: string|null, declared: int|null, continues: bool}|null
     */
    private function heading(string $line): ?array
    {
        if (1 === preg_match('/^\d/', $line)) {
            return null;
        }
        $declared = 1 === preg_match('/\((\d+)\)/', $line, $m) ? (int) $m[1] : null;
        $core = trim((string) preg_replace('/\([^)]*\)/', ' ', $line));
        $postcode = null;
        if (1 === preg_match(self::POSTCODE_SEARCH, $core, $pm, PREG_OFFSET_CAPTURE)) {
            $postcode = UkPostcode::normalise($pm[1][0] . $pm[2][0]);
            $core = substr($core, 0, $pm[0][1]);
        }
        // a card instruction written after the name ("FPS CARD PUT THROUGH DOOR") is not part of it
        $core = trim((string) preg_replace('/\b(FPS|FCS|CARDS?)\b.*$/', '', $core));
        $continues = 1 === preg_match('/\bCONTINUES\b/', $line);
        $name = trim((string) preg_replace('/\bCONTINUES\b/', '', $core));
        $name = trim((string) preg_replace('/\s+/', ' ', $name), " \t,.-");
        if (!$this->looksLikeAStreet($name, $postcode, $declared, $continues)) {
            return null;
        }
        return ['name' => $this->capitalise($name), 'postcode' => $postcode, 'declared' => $declared, 'continues' => $continues];
    }

    /**
     * Whether the name read from a heading line is a street: all capitals (or, with a postcode after
     * it, mixed case such as "Hilltop AB1 2CD"), and either a postcode, a count, "continues" or a
     * street word (Road, Crescent...) says so.
     */
    private function looksLikeAStreet(string $name, ?string $postcode, ?int $declared, bool $continues): bool
    {
        $mixedCaseOk = null !== $postcode && 1 !== preg_match('/\d/', $name) && count(explode(' ', $name)) <= 5;
        $shapeOk = '' !== $name
            && ($mixedCaseOk || (1 !== preg_match('/[a-z]/', $name) && 1 === preg_match('/\p{Lu}{2}/u', $name)));
        $hasStreetWord = [] !== array_intersect(explode(' ', $name), self::STREET_WORDS);
        return $shapeOk && (null !== $postcode || null !== $declared || $continues || $hasStreetWord);
    }

    private function house(string $line, int $lineNo): ?RunHouse
    {
        if (1 === preg_match('/^(DO NOT CLEAN|CANCELLED)\s+(\d{1,4}[A-Z]?)\s*$/i', $line, $m)) {
            return new RunHouse(strtoupper($m[2]), $line, $this->flags($m[1]), $lineNo);
        }
        if (1 !== preg_match('/^(\d{1,4}[A-Za-z]?)(?![A-Za-z0-9])\s*(.*)$/', $line, $m)) {
            return null;
        }
        return new RunHouse(strtoupper($m[1]), trim($m[2]), $this->flags($m[2]), $lineNo);
    }

    /**
     * @return list<string>
     */
    private function flags(string $note): array
    {
        $note = strtolower($note);
        $found = [];
        $rules = [
            RunHouse::FLAG_CANCELLED => '/cancel/',
            RunHouse::FLAG_DO_NOT_CLEAN => "/do not clean|don'?t clean|not to be cleaned/",
            RunHouse::FLAG_FRONTS_ONLY => '/fronts? only/',
            RunHouse::FLAG_NEW => '/\bnew\b/',
            RunHouse::FLAG_BI_MONTHLY => '/bi[\s-]?monthly/',
            RunHouse::FLAG_CARD => '/\bcards?\b/',
            RunHouse::FLAG_KNOCK => '/knock/',
            RunHouse::FLAG_EXTRA => '/extra/',
        ];
        foreach ($rules as $flag => $pattern) {
            if (1 === preg_match($pattern, $note)) {
                $found[] = $flag;
            }
        }
        return $found;
    }

    private function capitalise(string $name): string
    {
        return mb_convert_case(mb_strtolower($name), MB_CASE_TITLE);
    }
}
