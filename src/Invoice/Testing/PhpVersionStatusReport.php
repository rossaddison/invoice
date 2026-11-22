<?php

declare(strict_types=1);

namespace App\Invoice\Testing;

/**
 * Builds the daily PHP-version report from files the workflow has already put on disk, and touches no network:
 * .github/workflows/php-version-check.yml fetches php.net with curl and probes each environment, then calls run().
 *
 *  - <runsDirectory>/*.json:        {"where":"ubuntu","branch":"8.4","version":"8.4.28"}, one per probed environment
 *  - <latestDirectory>/<branch>.json: php.net's `releases/index.php?json&max=1&version=<branch>` response
 *  - $expect: "ubuntu:8.4,windows:8.5,..." every environment:branch that should have reported, so one that did not
 *    shows as unknown instead of silently disappearing.
 *
 * Writes the shields.io endpoint JSON to $badgeFile, and optionally a markdown table to $summaryFile.
 */
final class PhpVersionStatusReport
{
    /** @return string the markdown table */
    public static function run(
        string $runsDirectory,
        string $latestDirectory,
        string $expect,
        string $badgeFile,
        string $summaryFile = '',
        ?string $checkedOn = null,
    ): string {
        $expected = [];
        $branches = [];
        foreach (explode(',', $expect) as $pair) {
            [$where, $branch] = array_pad(explode(':', trim($pair), 2), 2, '');
            if ($where !== '' && $branch !== '') {
                $expected[] = [$where, $branch];
                $branches[$branch] = true;
            }
        }

        $latest = [];
        foreach (array_keys($branches) as $branch) {
            $json = @file_get_contents(rtrim($latestDirectory, '/\\') . '/' . $branch . '.json');
            $latest[$branch] = $json === false ? null : PhpVersionStatus::latestFromReleaseJson($json);
        }

        $date = $checkedOn ?? gmdate('Y-m-d');
        $rows = PhpVersionStatus::rows($latest, $expected, self::runs($runsDirectory));
        file_put_contents(
            $badgeFile,
            (string) json_encode(PhpVersionStatus::badge($rows, $date), JSON_UNESCAPED_SLASHES) . "\n",
        );
        $markdown = PhpVersionStatus::summaryMarkdown($rows, $date);
        if ($summaryFile !== '') {
            file_put_contents($summaryFile, $markdown, FILE_APPEND);
        }
        return $markdown;
    }

    /** @return list<array{where: string, branch: string, version: string|null}> */
    private static function runs(string $runsDirectory): array
    {
        $runs = [];
        $files = glob(rtrim($runsDirectory, '/\\') . '/*.json');
        foreach ($files === false ? [] : $files as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (!is_array($data)) {
                continue;
            }
            $where = self::stringField($data, 'where');
            $branch = self::stringField($data, 'branch');
            if ($where === null || $branch === null) {
                continue;
            }
            $runs[] = [
                'where' => $where,
                'branch' => $branch,
                'version' => self::stringField($data, 'version'),
            ];
        }
        return $runs;
    }

    /**
     * A non-empty string field of a decoded JSON object, or null when it is missing, empty or not a string. An empty
     * version is what a probe that failed to install PHP writes, and it must read as "not reported".
     *
     * @param array<array-key, mixed> $data
     */
    private static function stringField(array $data, string $key): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '' ? $data[$key] : null;
    }
}
