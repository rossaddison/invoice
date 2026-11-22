<?php

declare(strict_types=1);

namespace App\Invoice\Testing;

/**
 * Compares the PHP patch release each CI environment actually runs with the latest release php.net lists for that
 * branch, and turns the result into a shields.io endpoint badge, so the README shows at a glance whether CI is
 * behind. Used daily by .github/workflows/php-version-check.yml.
 *
 * A leg that could not be probed counts as unknown, never as current: a silent gap must not look green.
 */
final class PhpVersionStatus
{
    public const string CURRENT = 'current';
    public const string BEHIND = 'behind';
    public const string UNKNOWN = 'unknown';

    /**
     * The version in php.net's `releases/index.php?json&version=<branch>&max=1`, whose only key is the version.
     */
    public static function latestFromReleaseJson(string $json): ?string
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || $decoded === []) {
            return null;
        }
        $version = (string) array_key_first($decoded);
        return preg_match('/^\d+\.\d+\.\d+$/', $version) === 1 ? $version : null;
    }

    /**
     * @param array<string, string|null>                $latest   branch => latest version, null when unknown
     * @param list<array{0: string, 1: string}>         $expected [environment, branch] pairs that must be reported
     * @param list<array{where: string, branch: string, version: string|null}> $runs what each leg reported
     * @return list<array{where: string, branch: string, running: string|null, latest: string|null, state: string}>
     */
    public static function rows(array $latest, array $expected, array $runs): array
    {
        $reported = [];
        foreach ($runs as $run) {
            $reported[$run['where'] . '|' . $run['branch']] = $run['version'];
        }
        $rows = [];
        foreach ($expected as [$where, $branch]) {
            $running = $reported[$where . '|' . $branch] ?? null;
            $newest = $latest[$branch] ?? null;
            $rows[] = [
                'where' => $where,
                'branch' => $branch,
                'running' => $running,
                'latest' => $newest,
                'state' => self::state($running, $newest),
            ];
        }
        return $rows;
    }

    private static function state(?string $running, ?string $latest): string
    {
        if ($running === null || $running === '' || $latest === null) {
            return self::UNKNOWN;
        }
        return version_compare($running, $latest, '<') ? self::BEHIND : self::CURRENT;
    }

    /**
     * @param list<array{where: string, branch: string, running: string|null, latest: string|null, state: string}> $rows
     * @return array{schemaVersion: int, label: string, message: string, color: string, cacheSeconds: int}
     */
    public static function badge(array $rows, string $checkedOn): array
    {
        $behind = array_values(array_filter($rows, static fn (array $row): bool => $row['state'] === self::BEHIND));
        $unknown = array_filter($rows, static fn (array $row): bool => $row['state'] === self::UNKNOWN);

        if ($behind !== []) {
            $message = 'BEHIND: ' . implode(', ', array_map(
                static fn (array $row): string => $row['where'] . ' ' . $row['branch']
                    . ' (' . ($row['running'] ?? '?') . ' < ' . ($row['latest'] ?? '?') . ')',
                $behind,
            ));
            $color = 'red';
        } elseif ($rows === [] || $unknown !== []) {
            $message = 'unverified: ' . count($unknown) . ' of ' . count($rows) . ' not checked';
            $color = 'yellow';
        } else {
            $message = 'all on latest';
            $color = 'brightgreen';
        }

        return [
            'schemaVersion' => 1,
            'label' => 'PHP in CI (' . $checkedOn . ')',
            'message' => $message,
            'color' => $color,
            'cacheSeconds' => 3600,
        ];
    }

    /**
     * @param list<array{where: string, branch: string, running: string|null, latest: string|null, state: string}> $rows
     */
    public static function summaryMarkdown(array $rows, string $checkedOn): string
    {
        $lines = [
            '## PHP in CI vs latest release (' . $checkedOn . ')',
            '',
            '| Environment | Branch | Running | Latest | State |',
            '|---|---|---|---|---|',
        ];
        foreach ($rows as $row) {
            $lines[] = '| ' . $row['where'] . ' | ' . $row['branch'] . ' | ' . ($row['running'] ?? 'not reported')
                . ' | ' . ($row['latest'] ?? 'unknown') . ' | ' . $row['state'] . ' |';
        }
        return implode("\n", $lines) . "\n";
    }
}
