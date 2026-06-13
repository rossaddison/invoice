#!/usr/bin/env php
<?php

/**
 * Checks whether the local OpenPEPPOL VEFA code-list XML files in
 * src/Invoice/Helpers/Peppol/DownloadedXml/ are up-to-date by comparing their
 * recorded download date against the last GitHub commit date for each file on
 * the OpenPEPPOL/peppol-bis-invoice-3 master branch.
 *
 * Usage:
 *   php bin/check-peppol-codelists.php
 *
 * Optionally set GITHUB_TOKEN in your environment to raise the rate limit from
 * 60 to 5000 requests/hour:
 *   $env:GITHUB_TOKEN = "ghp_..."
 *
 * Exit codes:
 *   0  — all files up-to-date (or indeterminate)
 *   1  — one or more files are stale
 */

declare(strict_types=1);

// ── Config ────────────────────────────────────────────────────────────────────

const GITHUB_API   = 'https://api.github.com';
const GITHUB_REPO  = 'OpenPEPPOL/peppol-bis-invoice-3';
const GITHUB_REF   = 'master';
const README_PATH  = __DIR__ . '/../src/Invoice/Helpers/Peppol/DownloadedXml/README.md';

/**
 * Map: local filename => path within the OpenPEPPOL GitHub repository.
 * All five files exist under structure/codelist/ on the master branch.
 */
const FILE_MAP = [
    'eas.xml'      => 'structure/codelist/eas.xml',
    'icd.xml'      => 'structure/codelist/icd.xml',
    'UNCL5305.xml' => 'structure/codelist/UNCL5305.xml',
    'UNCL7161.xml' => 'structure/codelist/UNCL7161.xml',
    'uncl7143.xml' => 'structure/codelist/UNCL7143.xml',
];

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Parse the "Downloaded" dates out of the README.md table.
 * Looks for lines like:  | `eas.xml` | ... | 2026-06-12 | ... |
 *
 * @return array<string, string>  filename => YYYY-MM-DD
 */
function parseDownloadedDates(string $readmePath): array
{
    $dates = [];
    foreach (file($readmePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        // Match: | `filename` | ... | YYYY-MM-DD | ... |
        if (preg_match('/\|\s+`([^`]+)`\s+\|.*\|\s+(\d{4}-\d{2}-\d{2})\s+\|/', $line, $m)) {
            $dates[$m[1]] = $m[2];
        }
    }
    return $dates;
}

/**
 * Fetch the last commit date for a file path in the GitHub repository.
 * Returns an ISO 8601 string (e.g. "2024-11-03T14:22:11Z") or null on error.
 */
function fetchLastCommitDate(string $filePath, ?string $token): ?string
{
    $url = GITHUB_API . '/repos/' . GITHUB_REPO . '/commits?path='
         . urlencode($filePath) . '&sha=' . GITHUB_REF . '&per_page=1';

    $headers = [
        'User-Agent: rossaddison/invoice-codelist-checker',
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
    ];
    if ($token !== null && $token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $status !== 200) {
        return null;
    }

    /** @var list<array{commit: array{committer: array{date: string}}}> $data */
    $data = json_decode((string) $body, true);
    if (!is_array($data) || $data === [] || !isset($data[0]['commit']['committer']['date'])) {
        return null;
    }

    return $data[0]['commit']['committer']['date'];
}

/**
 * Compare two date strings (YYYY-MM-DD or ISO 8601) and return:
 *   'UP-TO-DATE'    local download is on or after last GitHub commit
 *   'STALE'         GitHub has a newer commit than local download
 *   'UNKNOWN'       could not determine
 */
function compareDate(string $downloadedDate, string $githubDate): string
{
    $local  = date_create(substr($downloadedDate, 0, 10));
    $remote = date_create(substr($githubDate, 0, 10));
    if ($local === false || $remote === false) {
        return 'UNKNOWN';
    }
    return ($local >= $remote) ? 'UP-TO-DATE' : 'STALE';
}

// ── Main ──────────────────────────────────────────────────────────────────────

$token = getenv('GITHUB_TOKEN') ?: null;

if (!file_exists(README_PATH)) {
    fwrite(STDERR, "ERROR: README not found at " . README_PATH . PHP_EOL);
    exit(1);
}

$downloadedDates = parseDownloadedDates(README_PATH);

$anyStale = false;

printf("%-20s  %-12s  %-26s  %s\n", 'File', 'Downloaded', 'Last GitHub Commit', 'Status');
echo str_repeat('-', 80) . PHP_EOL;

foreach (FILE_MAP as $filename => $repoPath) {
    $downloadedDate = $downloadedDates[$filename] ?? 'unknown';
    $githubDate     = fetchLastCommitDate($repoPath, $token);

    if ($githubDate === null) {
        $status      = 'UNKNOWN (API error)';
        $displayDate = '—';
    } else {
        $displayDate = substr($githubDate, 0, 10);
        $status      = compareDate($downloadedDate, $githubDate);
        if ($status === 'STALE') {
            $anyStale = true;
        }
    }

    $statusLabel = match (true) {
        str_starts_with($status, 'STALE')      => "\033[31m" . $status . "\033[0m",
        str_starts_with($status, 'UP-TO-DATE') => "\033[32m" . $status . "\033[0m",
        default                                 => "\033[33m" . $status . "\033[0m",
    };

    printf("%-20s  %-12s  %-26s  %s\n", $filename, $downloadedDate, $displayDate, $statusLabel);
}

echo PHP_EOL;

if ($anyStale) {
    echo "\033[31mOne or more files are stale. Download updated versions and update the\033[0m\n";
    echo "\033[31mDownloaded date in src/Invoice/Helpers/Peppol/DownloadedXml/README.md.\033[0m\n";
    exit(1);
}

echo "\033[32mAll files are up-to-date.\033[0m\n";
exit(0);
