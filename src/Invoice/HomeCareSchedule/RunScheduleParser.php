<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

use DateTimeImmutable;

/**
 * Reads the run schedule as it is actually sent round (typed or WhatsApp text), not a tidy format:
 *
 *   OCTOBER SCHEDULE                          <- the month can be in a heading
 *   5 - North 2 (Andy & Dan)              <- day - run (crew)
 *   6 - South 1  (" & ")                <- ditto marks: the same crew as the line above
 *   13 - West                                   <- the crew can be on the next line,
 *   (Ross.Andy.Dan)                           <-   with dots, ampersands, commas or "and"
 *   2 North 1 / 12Hill / 4           <- no dash, no space, or a bare day (an open day)
 *   Andrew is every Monday and Tuesday        <- a standing rule: on those runs' crews
 *
 * Divider lines of dots or dashes are ignored. Nothing is saved: the result is what the preview
 * shows, and anything it could not make sense of is returned as a warning rather than guessed.
 * Pure, with no database: matching run and worker names to real ones happens afterwards.
 */
final readonly class RunScheduleParser
{
    private const array MONTHS = [
        'january' => 1, 'jan' => 1, 'february' => 2, 'feb' => 2, 'march' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4, 'may' => 5, 'june' => 6, 'jun' => 6, 'july' => 7, 'jul' => 7,
        'august' => 8, 'aug' => 8, 'september' => 9, 'sept' => 9, 'sep' => 9, 'october' => 10, 'oct' => 10,
        'november' => 11, 'nov' => 11, 'december' => 12, 'dec' => 12,
    ];

    private const array WEEKDAYS = [
        'monday' => 1, 'mon' => 1, 'tuesday' => 2, 'tues' => 2, 'tue' => 2, 'wednesday' => 3, 'wed' => 3,
        'thursday' => 4, 'thurs' => 4, 'thu' => 4, 'friday' => 5, 'fri' => 5, 'saturday' => 6, 'sat' => 6,
        'sunday' => 7, 'sun' => 7,
    ];

    /**
     * @param int $year the year the schedule is for (the text rarely says)
     * @param int|null $month used only when the text itself names no month
     */
    public function parse(string $text, int $year, ?int $month = null): ParsedSchedule
    {
        $lines = $this->lines($text);
        $month = $this->monthFrom($lines) ?? $month;
        $warnings = [];
        if (null === $month) {
            return new ParsedSchedule($year, 0, [], [], ['No month found in the text: choose the month.']);
        }

        $rules = [];
        /** @var list<array{day: int, run: ?string, crew: ?list<string>, ditto: bool, line: int}> $raw */
        $raw = [];
        foreach ($lines as [$lineNo, $line]) {
            $rule = $this->ruleFrom($line);
            if (null !== $rule) {
                $rules[] = $rule;
            } elseif (preg_match('/^\((.*)\)$/u', $line, $m) && [] !== $raw && null === $raw[count($raw) - 1]['crew']) {
                // a crew on a line of its own belongs to the day above
                $crew = $this->crewFrom($m[1]);
                $raw[count($raw) - 1]['crew'] = $crew ?? [];
                $raw[count($raw) - 1]['ditto'] = null === $crew;
            } elseif (preg_match('/^(\d{1,2})(?!\d)\s*(?:[-–—:]\s*)?(.*)$/u', $line, $m)) {
                $raw[] = $this->entryFrom((int) $m[1], trim($m[2]), $lineNo);
            } elseif (!$this->isHeading($line)) {
                $warnings[] = sprintf('Line %d was not understood and was skipped: "%s"', $lineNo, mb_substr($line, 0, 80));
            }
        }

        return new ParsedSchedule($year, $month, $this->entries(array_values($raw), $year, $month, $rules, $warnings), $rules, $warnings);
    }

    /**
     * @param list<array{day: int, run: ?string, crew: ?list<string>, ditto: bool, line: int}> $raw
     * @param list<WeeklyRule> $rules
     * @param list<string> $warnings
     * @return list<ScheduleEntry>
     */
    private function entries(array $raw, int $year, int $month, array $rules, array &$warnings): array
    {
        $entries = [];
        $previousCrew = [];
        $seen = [];
        foreach ($raw as $item) {
            $date = DateTimeImmutable::createFromFormat('!Y-n-j', sprintf('%d-%d-%d', $year, $month, $item['day']));
            if (false === $date || (int) $date->format('j') !== $item['day']) {
                $warnings[] = sprintf('Line %d: day %d does not exist in that month and was skipped.', $item['line'], $item['day']);
                continue;
            }
            $key = $date->format('Y-m-d');
            if (isset($seen[$key])) {
                $warnings[] = sprintf('Line %d: %s appears more than once; check which run it is.', $item['line'], $key);
            }
            $seen[$key] = true;

            $crew = $item['ditto'] ? $previousCrew : ($item['crew'] ?? []);
            $weekday = (int) $date->format('N');
            $ruleNames = null === $item['run'] ? [] : $this->ruleNames($rules, $weekday, $crew);
            if (null !== $item['run']) {
                $previousCrew = $crew;
            }
            $entries[] = new ScheduleEntry($key, $weekday, $item['run'], $crew, $ruleNames, $item['ditto'], $item['line']);
        }
        return $entries;
    }

    /**
     * @return array{day: int, run: ?string, crew: ?list<string>, ditto: bool, line: int}
     */
    private function entryFrom(int $day, string $rest, int $lineNo): array
    {
        $crew = null;
        $ditto = false;
        if (preg_match('/^(.*?)\s*\(([^)]*)\)\s*$/u', $rest, $m)) {
            $rest = trim($m[1]);
            $crew = $this->crewFrom($m[2]);
            $ditto = null === $crew;
        }
        $run = trim($rest, " \t-–—:,.");
        return ['day' => $day, 'run' => '' === $run ? null : $run, 'crew' => $crew, 'ditto' => $ditto, 'line' => $lineNo];
    }

    /**
     * The crew names, or null when the text is only ditto marks (the same crew as the line above).
     *
     * @return list<string>|null
     */
    private function crewFrom(string $text): ?array
    {
        if ('' === trim($text, " \t\"'“”‘’&,.-") ) {
            return null;
        }
        $names = [];
        foreach (preg_split('/\s*(?:[&,\/.+]|\band\b)\s*/iu', $text) ?: [] as $name) {
            $name = trim($name, " \t\"'“”‘’");
            if ('' !== $name) {
                $names[] = mb_convert_case($name, MB_CASE_TITLE);
            }
        }
        return array_values(array_unique($names));
    }

    /**
     * Rule names for a run day: those whose weekday matches and who are not already on the crew.
     *
     * @param list<WeeklyRule> $rules
     * @param list<string> $crew
     * @return list<string>
     */
    private function ruleNames(array $rules, int $weekday, array $crew): array
    {
        $names = [];
        foreach ($rules as $rule) {
            $name = mb_convert_case($rule->name, MB_CASE_TITLE);
            if (in_array($weekday, $rule->weekdays, true) && !in_array($name, $crew, true)) {
                $names[] = $name;
            }
        }
        return array_values(array_unique($names));
    }

    private function ruleFrom(string $line): ?WeeklyRule
    {
        if (!preg_match('/^(\p{L}[\p{L}\'\- ]*?)\s+is\s+(?:every|on)\s+(.+)$/iu', $line, $m)) {
            return null;
        }
        $days = [];
        foreach (preg_split('/\s*(?:,|&|\band\b)\s*/iu', $m[2]) ?: [] as $word) {
            $day = self::WEEKDAYS[mb_strtolower(trim($word, " .s"))] ?? self::WEEKDAYS[mb_strtolower(trim($word))] ?? null;
            if (null !== $day) {
                $days[] = $day;
            }
        }
        return [] === $days ? null : new WeeklyRule(trim($m[1]), array_values(array_unique($days)));
    }

    /**
     * A heading line (mentions the schedule or a month) rather than something dropped by mistake.
     */
    private function isHeading(string $line): bool
    {
        return (bool) preg_match('/\bschedule\b/i', $line);
    }

    /**
     * The month from a heading line ("OCTOBER SCHEDULE", "the schedule for the month of November").
     *
     * @param list<array{0: int, 1: string}> $lines
     */
    private function monthFrom(array $lines): ?int
    {
        foreach ($lines as [, $line]) {
            if (!preg_match('/\bschedule\b|\bmonth\b/i', $line)) {
                continue;
            }
            foreach (preg_split('/[^\p{L}]+/u', mb_strtolower($line)) ?: [] as $word) {
                if (isset(self::MONTHS[$word])) {
                    return self::MONTHS[$word];
                }
            }
        }
        return null;
    }

    /**
     * Cleans the pasted text into numbered lines: smart quotes made plain, divider lines of dots and
     * dashes dropped, and a heading glued to the next sentence by a colon split in two.
     *
     * @return list<array{0: int, 1: string}> [line number, text]
     */
    private function lines(string $text): array
    {
        $text = strtr($text, ["\u{00a0}" => ' ', '“' => '"', '”' => '"', '‘' => "'", '’' => "'"]);
        $out = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $i => $raw) {
            $line = trim($raw);
            if ('' === $line || preg_match('/^[.\-_=…·\s]+$/u', $line)) {
                continue;
            }
            if (preg_match('/^(.*\bschedule\b[^:]*):\s*(\S.*)$/iu', $line, $m)) {
                $out[] = [$i + 1, trim($m[1])];
                $out[] = [$i + 1, trim($m[2])];
                continue;
            }
            $out[] = [$i + 1, $line];
        }
        return $out;
    }
}
