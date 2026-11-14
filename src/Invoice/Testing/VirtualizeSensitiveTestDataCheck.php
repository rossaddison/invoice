<?php

declare(strict_types=1);

namespace App\Invoice\Testing;

use function array_key_exists;
use function array_keys;
use function explode;
use function implode;
use function in_array;
use function intdiv;
use function preg_match_all;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;
use function usort;

use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;

/**
 * Keeps real people's data out of tests and fixtures.
 *
 * Test data must be virtual: values that look like the real thing (so the code under test sees
 * realistic shapes) but cannot belong to anybody. This class does two things with that rule:
 *
 *  - {@see self::check()} reports every value in a text that looks real, so a commit hook, a CI job
 *    and a test can refuse it; and
 *  - {@see self::virtualize()} replaces those values with virtual ones, the same real value always
 *    becoming the same virtual value inside one call, so a real run sheet can be turned into a
 *    fixture that keeps its shape without keeping the data.
 *
 * What counts as virtual, and why it cannot clash with a real person:
 *  - email: an RFC 2606 / RFC 6761 reserved domain (example.com, example.org, example.net, *.test,
 *    *.example, *.invalid, *.localhost);
 *  - telephone: a number Ofcom reserves for drama use (01632 960xxx, 07700 900xxx, 020 7946 0xxx and
 *    the other reserved ranges), which are never allocated to a subscriber;
 *  - postcode: an outward code starting ZZ, which is not a UK postcode area;
 *  - titled name: a surname that starts with Virtual, Test, Example or Sample.
 *
 * A line can opt out with {@see self::IGNORE_MARKER} (for a test that needs a real-looking value on
 * purpose, such as a test of this class), so an exception is always visible in review.
 *
 * Findings never repeat the value they found, only its kind, line and length, so a CI log or a
 * terminal does not become the next place the data leaks.
 */
final readonly class VirtualizeSensitiveTestDataCheck
{
    public const string RULE_EMAIL = 'email';
    public const string RULE_PHONE = 'phone';
    public const string RULE_POSTCODE = 'postcode';
    public const string RULE_TITLED_NAME = 'titled-name';

    /** Put this on a line to skip it, e.g. `// sensitive-data-check: ignore` */
    public const string IGNORE_MARKER = 'sensitive-data-check: ignore';

    private const array PATTERNS = [
        self::RULE_EMAIL => '/[A-Za-z0-9._%+\-]+@([A-Za-z0-9\-]+(?:\.[A-Za-z0-9\-]+)*\.[A-Za-z]{2,})/',
        // 0 or +44, then a UK area or mobile code and the rest; spaces or hyphens between digit groups.
        self::RULE_PHONE => '/(?<![\d.])(?:\+44[\s\-]?\(?0?\)?|0)[1-37-9]\d{1,3}[\s\-]?\d{3,4}[\s\-]?\d{3,4}(?!\d)/',
        self::RULE_POSTCODE => '/\b([A-Z]{1,2}\d[A-Z\d]?) ?(\d[A-Z]{2})\b/i',
        self::RULE_TITLED_NAME => '/\b(Mr|Mrs|Ms|Miss|Mx|Dr)\.? +([A-Z][a-z]+(?:[\-\'][A-Z][a-z]+)?)\b/',
    ];

    /** Ofcom drama ranges, as national-format digit prefixes. */
    private const array VIRTUAL_PHONE_PREFIXES = [
        '01632960', '07700900', '02079460', '01134960', '01214960', '01174960', '01414960',
        '01914980', '02890180', '02920180', '01618960',
    ];

    private const array VIRTUAL_EMAIL_DOMAINS = ['example.com', 'example.org', 'example.net'];

    private const array VIRTUAL_EMAIL_SUFFIXES = ['.test', '.example', '.invalid', '.localhost'];

    private const array VIRTUAL_SURNAME_WORDS = ['Virtual', 'Test', 'Example', 'Sample'];

    /**
     * @return list<array{rule: string, line: int, hint: string}> every real-looking value, in text order
     */
    public function check(string $text): array
    {
        $findings = [];
        foreach (explode("\n", $text) as $index => $line) {
            if (str_contains($line, self::IGNORE_MARKER)) {
                continue;
            }
            foreach ($this->realLookingValues($line) as $found) {
                $findings[] = [
                    'rule' => $found['rule'],
                    'line' => $index + 1,
                    'hint' => sprintf('%d characters', strlen($found['value'])),
                ];
            }
        }
        return $findings;
    }

    public function isVirtual(string $text): bool
    {
        return $this->check($text) === [];
    }

    /**
     * The text with every real-looking value replaced by a virtual one. The same real value gets the
     * same virtual value everywhere in the text, so relationships in the data (the same client on two
     * lines) survive. Lines carrying the ignore marker are left as they are. Virtualizing virtual text
     * changes nothing.
     */
    public function virtualize(string $text): string
    {
        /** @var array<string, string> $assigned rule|real value => virtual value, shared across lines */
        $assigned = [];
        $lines = explode("\n", $text);
        foreach ($lines as $index => $line) {
            if (str_contains($line, self::IGNORE_MARKER)) {
                continue;
            }
            foreach (array_keys(self::PATTERNS) as $rule) {
                $line = $this->replaceRule($rule, $line, $assigned);
            }
            $lines[$index] = $line;
        }
        return implode("\n", $lines);
    }

    /**
     * @param array<string, string> $assigned
     */
    private function replaceRule(string $rule, string $line, array &$assigned): string
    {
        $replaced = preg_replace_callback(
            self::PATTERNS[$rule],
            function (array $match) use ($rule, &$assigned): string {
                /** @var array<int, string> $match */
                if ($this->isVirtualValue($rule, $match)) {
                    return $match[0];
                }
                $key = $rule . '|' . strtolower($match[0]);
                if (!array_key_exists($key, $assigned)) {
                    $assigned[$key] = $this->virtualValue($rule, $this->assignedCount($assigned, $rule) + 1, $match);
                }
                return $assigned[$key];
            },
            $line,
        );
        return $replaced ?? $line;
    }

    /**
     * @param array<string, string> $assigned
     */
    private function assignedCount(array $assigned, string $rule): int
    {
        $count = 0;
        foreach (array_keys($assigned) as $key) {
            if (str_starts_with($key, $rule . '|')) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return list<array{offset: int, rule: string, value: string}> values that look real, in text order
     */
    private function realLookingValues(string $line): array
    {
        $found = [];
        foreach (self::PATTERNS as $rule => $pattern) {
            if (preg_match_all($pattern, $line, $sets, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
                continue;
            }
            foreach ($sets as $set) {
                $plain = [];
                foreach ($set as $index => $part) {
                    $plain[(int) $index] = $part[0];
                }
                if (!$this->isVirtualValue($rule, $plain)) {
                    $found[] = ['offset' => $set[0][1], 'rule' => $rule, 'value' => $plain[0]];
                }
            }
        }
        usort($found, static fn (array $a, array $b): int => $a['offset'] <=> $b['offset']);
        return $found;
    }

    /**
     * @param array<int, string> $match the regular-expression match, index 0 the whole value
     */
    private function isVirtualValue(string $rule, array $match): bool
    {
        return match ($rule) {
            self::RULE_EMAIL => $this->isVirtualDomain(strtolower($match[1] ?? '')),
            self::RULE_PHONE => $this->isVirtualPhone($match[0]),
            self::RULE_POSTCODE => str_starts_with(strtoupper($match[1] ?? ''), 'ZZ'),
            self::RULE_TITLED_NAME => $this->isVirtualSurname($match[2] ?? ''),
            default => false,
        };
    }

    private function isVirtualDomain(string $domain): bool
    {
        if (in_array($domain, self::VIRTUAL_EMAIL_DOMAINS, true)) {
            return true;
        }
        foreach (self::VIRTUAL_EMAIL_SUFFIXES as $suffix) {
            if (str_ends_with($domain, $suffix)) {
                return true;
            }
        }
        return false;
    }

    private function isVirtualPhone(string $number): bool
    {
        $digits = (string) preg_replace('/\D/', '', $number);
        if (str_starts_with($digits, '44')) {
            $digits = '0' . substr($digits, 2);
        }
        foreach (self::VIRTUAL_PHONE_PREFIXES as $prefix) {
            if (str_starts_with($digits, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private function isVirtualSurname(string $surname): bool
    {
        foreach (self::VIRTUAL_SURNAME_WORDS as $word) {
            if (str_starts_with($surname, $word)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<int, string> $match
     */
    private function virtualValue(string $rule, int $number, array $match): string
    {
        return match ($rule) {
            self::RULE_EMAIL => sprintf('virtual%d@example.test', $number),
            self::RULE_PHONE => sprintf('01632 960%03d', $number % 1000),
            self::RULE_POSTCODE => sprintf('ZZ%d %dAA', ($number % 9) + 1, intdiv($number, 9) % 9 + 1),
            self::RULE_TITLED_NAME => sprintf('%s Virtual%d', $match[1] ?? 'Mr', $number),
            default => $match[0],
        };
    }
}
