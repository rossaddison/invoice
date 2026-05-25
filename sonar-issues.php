<?php

/**
 * Fetches open SonarCloud issues for this project and prints them in
 * Psalm-style format:
 *
 *   ERROR: php:S1848 - src/Invoice/Foo.php:42 - Objects should not be created to be dropped immediately
 *
 * Usage:
 *   php sonar-issues.php                    # all open issues
 *   php sonar-issues.php --pr=862           # issues on a pull request
 *   php sonar-issues.php --type=BUG         # filter by type (BUG, VULNERABILITY, CODE_SMELL)
 *   php sonar-issues.php --severity=MAJOR   # filter by severity
 *   php sonar-issues.php --hotspots         # security hotspots instead of issues
 *
 * Set SONAR_TOKEN in your environment or .env before running:
 *   $env:SONAR_TOKEN = "your-token-here"
 */

declare(strict_types=1);

// ── Config ────────────────────────────────────────────────────────────────────
const SONAR_HOST    = 'https://sonarcloud.io';
const PROJECT_KEY   = 'rossaddison_invoice';
const SONAR_TOKEN   = ''; // fallback — prefer environment variable

// ── Parse arguments ───────────────────────────────────────────────────────────
$args     = array_slice($argv, 1);
$pr       = null;
$type     = null;
$severity = null;
$hotspots = false;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--pr='))       { $pr       = substr($arg, 5); }
    if (str_starts_with($arg, '--type='))     { $type     = strtoupper(substr($arg, 7)); }
    if (str_starts_with($arg, '--severity=')) { $severity = strtoupper(substr($arg, 11)); }
    if ($arg === '--hotspots')                { $hotspots = true; }
}

// ── Token ─────────────────────────────────────────────────────────────────────
$token = $_SERVER['SONAR_TOKEN'] ?? getenv('SONAR_TOKEN') ?: SONAR_TOKEN;
if ($token === '') {
    fwrite(STDERR, "ERROR: Set SONAR_TOKEN environment variable first.\n");
    fwrite(STDERR, "  PowerShell: \$env:SONAR_TOKEN = 'your-token'\n");
    exit(1);
}

// ── Fetch ─────────────────────────────────────────────────────────────────────
function sonar_get(string $path, array $query, string $token): array
{
    $url = SONAR_HOST . $path . '?' . http_build_query($query);
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($raw === false || $err !== '') {
        fwrite(STDERR, "ERROR: Could not reach $url\n$err\n");
        exit(1);
    }
    if ($code === 401) {
        fwrite(STDERR, "ERROR: Unauthorised — check your SONAR_TOKEN\n");
        exit(1);
    }
    $decoded = json_decode($raw, true) ?? [];
    if (isset($decoded['errors'])) {
        fwrite(STDERR, "API ERROR: " . json_encode($decoded['errors']) . "\n");
        exit(1);
    }
    return $decoded;
}

// ── Collect all pages ─────────────────────────────────────────────────────────
function fetch_all_issues(string $token, ?string $pr, ?string $type, ?string $severity): array
{
    $issues = [];
    $page   = 1;

    do {
        $query = [
            'componentKeys' => PROJECT_KEY,
            'issueStatuses' => 'OPEN,CONFIRMED',
            'ps'           => 500,
            'p'            => $page,
        ];
        if ($pr !== null)       { $query['pullRequest'] = $pr; }
        if ($type !== null)     { $query['types']       = $type; }
        if ($severity !== null) { $query['severities']  = $severity; }

        $data    = sonar_get('/api/issues/search', $query, $token);
        $batch   = $data['issues'] ?? [];
        $issues  = array_merge($issues, $batch);
        $total   = $data['paging']['total'] ?? 0;
        $page++;
    } while (count($issues) < $total);

    return $issues;
}

function fetch_all_hotspots(string $token, ?string $pr): array
{
    $hotspots = [];
    $page     = 1;

    do {
        $query = [
            'projectKey' => PROJECT_KEY,
            'hotspotStatuses' => 'TO_REVIEW',
            'ps'         => 500,
            'p'          => $page,
        ];
        if ($pr !== null) { $query['pullRequest'] = $pr; }

        $data     = sonar_get('/api/hotspots/search', $query, $token);
        $batch    = $data['hotspots'] ?? [];
        $hotspots = array_merge($hotspots, $batch);
        $total    = $data['paging']['total'] ?? 0;
        $page++;
    } while (count($hotspots) < $total);

    return $hotspots;
}

// ── Format & print ────────────────────────────────────────────────────────────
$severityLabel = [
    'BLOCKER'  => 'BLOCKER',
    'CRITICAL' => 'CRITICAL',
    'MAJOR'    => 'ERROR',
    'MINOR'    => 'WARN',
    'INFO'     => 'INFO',
];

if ($hotspots) {
    $items = fetch_all_hotspots($token, $pr);
    foreach ($items as $h) {
        $file    = $h['component'] ?? '';
        $file    = preg_replace('/^' . preg_quote(PROJECT_KEY, '/') . ':/', '', $file);
        $line    = $h['line'] ?? '?';
        $rule    = $h['securityCategory'] ?? 'hotspot';
        $message = $h['message'] ?? '';
        echo "HOTSPOT: $rule - $file:$line - $message\n";
    }
    echo "\n" . count($items) . " hotspot(s)\n";
} else {
    $items = fetch_all_issues($token, $pr, $type, $severity);
    foreach ($items as $issue) {
        $sev     = $issue['severity'] ?? 'MAJOR';
        $label   = $severityLabel[$sev] ?? $sev;
        $rule    = $issue['rule'] ?? '';
        $file    = $issue['component'] ?? '';
        $file    = preg_replace('/^' . preg_quote(PROJECT_KEY, '/') . ':/', '', $file);
        $line    = $issue['line'] ?? '?';
        $message = $issue['message'] ?? '';
        echo "$label: $rule - $file:$line - $message\n";
    }
    echo "\n" . count($items) . " issue(s)\n";
}
