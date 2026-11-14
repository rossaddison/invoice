<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

/**
 * The import sheet: a CSV with one row per house, in driving order. {@see self::template()} makes
 * the blank (or pre-filled) sheet to download and {@see self::parse()} reads it back into the same
 * {@see ParsedRun} the pasted text produces, so it goes through the same preview and save.
 *
 * Columns: street, postcode, number, client_name, phone, price, note, flags. Flags are the
 * RunHouse::FLAG_* values separated by spaces (e.g. "fronts_only new"). Client names and phones
 * live only in the office; they are never shown to workers.
 */
final class RunSheetCsv
{
    /** The byte-order mark Excel puts at the start of a UTF-8 CSV. */
    private const string BOM = "\xEF\xBB\xBF";

    public const array COLUMNS = ['street', 'postcode', 'number', 'client_name', 'phone', 'price', 'note', 'flags'];

    private const array VALID_FLAGS = [
        RunHouse::FLAG_CANCELLED,
        RunHouse::FLAG_DO_NOT_CLEAN,
        RunHouse::FLAG_FRONTS_ONLY,
        RunHouse::FLAG_NEW,
        RunHouse::FLAG_BI_MONTHLY,
        RunHouse::FLAG_CARD,
        RunHouse::FLAG_KNOCK,
        RunHouse::FLAG_EXTRA,
        RunHouse::FLAG_PULL_POINT,
    ];

    /**
     * Whether the text is an import sheet (its first line is the header) rather than a pasted run.
     */
    public function isSheet(string $text): bool
    {
        $lines = preg_split('/\r\n|\r|\n/', ltrim($text, self::BOM), 2);
        $first = strtolower(str_replace('"', '', trim($lines[0] ?? '')));
        return str_starts_with($first, 'street,') || str_starts_with($first, 'street;');
    }

    /**
     * The sheet to download. With a run, it has a row per house of that run, ready for the names,
     * phones and prices to be filled in; without, only the header and one example row.
     */
    public function template(?ParsedRun $run = null): string
    {
        $rows = [self::COLUMNS];
        if (null === $run) {
            $rows[] = ['Example Crescent', 'AB1 2CD', '14', 'Mrs Smith', '07700 900123', '12.50', 'gate sticks', 'fronts_only'];
        } else {
            foreach ($run->segments as $segment) {
                foreach ($segment->houses as $house) {
                    $rows[] = [
                        $segment->street,
                        (string) $segment->postcode,
                        $house->number,
                        $house->clientName,
                        $house->phone,
                        null === $house->price ? '' : (string) $house->price,
                        $house->note,
                        implode(' ', $house->flags),
                    ];
                }
            }
        }
        $out = self::BOM;
        foreach ($rows as $row) {
            $out .= implode(',', array_map($this->quote(...), $row)) . "\r\n";
        }
        return $out;
    }

    /**
     * Reads the sheet. Rows of the same street that follow each other make one segment; the run is
     * named by $title. Problems (a row with no street or number, a bad price, an unknown flag) are
     * returned as warnings and the row is skipped only when it has no street or number.
     */
    public function parse(string $csv, string $title = ''): ParsedRun
    {
        $csv = ltrim($csv, self::BOM);
        $lines = preg_split('/\r\n|\r|\n/', $csv) ?: [];
        $first = $lines[0] ?? '';
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        /** @var list<string> $header */
        $header = array_map(static fn (?string $c): string => strtolower(trim((string) $c)), str_getcsv($first, $delimiter, '"', ''));
        $index = array_flip($header);

        $segments = [];
        $warnings = [];
        $street = null;
        $segPostcode = null;
        $segLine = 0;
        /** @var list<RunHouse> $houses */
        $houses = [];
        $seen = [];
        foreach ($lines as $i => $text) {
            if (0 === $i || '' === trim($text)) {
                continue;
            }
            $lineNo = $i + 1;
            $cells = str_getcsv($text, $delimiter, '"', '');
            $get = fn (string $column): string => isset($index[$column]) ? $this->unguard(trim((string) ($cells[$index[$column]] ?? ''))) : '';
            $rowStreet = $get('street');
            $number = strtoupper($get('number'));
            // the same house numbers the pasted-text parser reads: 14, 12A ("Flat 1" or "12-14" would collapse
            // onto one dwelling)
            if ('' === $rowStreet || 1 !== preg_match('/^\d{1,4}[A-Z]?$/', $number)) {
                $warnings[] = sprintf('Line %d skipped: it needs a street and a house number such as 14 or 12A.', $lineNo);
                continue;
            }
            $postcode = $this->postcode($get('postcode'));
            if (null === $postcode && '' !== $get('postcode')) {
                $warnings[] = sprintf('Line %d: "%s" is not a postcode, so it is left blank.', $lineNo, $get('postcode'));
            }
            $priceText = $get('price');
            $price = null;
            if ('' !== $priceText) {
                if (is_numeric($priceText) && (float) $priceText >= 0.0) {
                    $price = (float) $priceText;
                } else {
                    $warnings[] = sprintf('Line %d: "%s" is not a price, so the standard price is used.', $lineNo, $priceText);
                }
            }
            $flags = [];
            foreach (preg_split('/[\s,]+/', strtolower($get('flags'))) ?: [] as $flag) {
                if ('' === $flag) {
                    continue;
                }
                if (in_array($flag, self::VALID_FLAGS, true)) {
                    $flags[] = $flag;
                } else {
                    $warnings[] = sprintf('Line %d: unknown flag "%s" ignored.', $lineNo, $flag);
                }
            }
            $key = strtolower($rowStreet) . '|' . $number;
            if (isset($seen[$key])) {
                $warnings[] = sprintf('Line %d: %s %s is already on line %d.', $lineNo, $number, $rowStreet, $seen[$key]);
            }
            $seen[$key] = $lineNo;
            if (null === $street || strtolower($street) !== strtolower($rowStreet)) {
                if (null !== $street) {
                    $segments[] = new RunSegment($street, $segPostcode, null, false, $houses, [], $segLine);
                }
                $street = $rowStreet;
                $segPostcode = $postcode;
                $segLine = $lineNo;
                $houses = [];
            }
            $segPostcode ??= $postcode;
            $houses[] = new RunHouse($number, $get('note'), $flags, $lineNo, $get('client_name'), $get('phone'), $price);
        }
        if (null !== $street) {
            $segments[] = new RunSegment($street, $segPostcode, null, false, $houses, [], $segLine);
        }
        // a street that comes back later is a continuation of its earlier stretch
        $met = [];
        $result = [];
        foreach ($segments as $segment) {
            $name = strtolower($segment->street);
            $result[] = isset($met[$name])
                ? new RunSegment($segment->street, $segment->postcode ?? $met[$name], null, true, $segment->houses, [], $segment->line)
                : $segment;
            $met[$name] = $segment->postcode ?? ($met[$name] ?? null);
        }

        return new ParsedRun('' === $title ? 'Import sheet' : $title, [], $result, $warnings);
    }

    private function postcode(string $value): ?string
    {
        $clean = strtoupper((string) preg_replace('/\s+/', '', $value));
        if (1 !== preg_match('/^[A-Z]{1,2}\d[A-Z\d]?\d[A-Z]{2}$/', $clean)) {
            return null;
        }
        return substr($clean, 0, -3) . ' ' . substr($clean, -3);
    }

    /**
     * Undoes {@see self::quote()}: a cell the template guarded with a leading apostrophe ("'+44 7700 900123")
     * is read back without it.
     */
    private function unguard(string $value): string
    {
        return strlen($value) > 1 && "'" === $value[0] && in_array($value[1], ['=', '+', '-', '@'], true)
            ? substr($value, 1)
            : $value;
    }

    private function quote(string $value): string
    {
        // a leading = + - @ would be run as a formula by a spreadsheet
        if ('' !== $value && in_array($value[0], ['=', '+', '-', '@'], true) && !is_numeric($value)) {
            $value = "'" . $value;
        }
        return '"' . str_replace('"', '""', $value) . '"';
    }
}
