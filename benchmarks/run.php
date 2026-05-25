#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Yii3-i Performance Benchmark Runner
 *
 * Runs all benchmark suites, prints a table, and appends results to
 * benchmarks/results/history.json for long-term trend tracking.
 *
 * Usage:
 *   php benchmarks/run.php            # run all suites
 *   php benchmarks/run.php --dry-run  # run but do not write to history.json
 *   php benchmarks/run.php --suite=di # run only one suite (di|injector|router|strings)
 */

require __DIR__ . '/../vendor/autoload.php';

// ── CLI flags ──────────────────────────────────────────────────────────────────
$dryRun  = in_array('--dry-run', $argv, true);
$suite   = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--suite=')) {
        $suite = strtolower(substr($arg, 8));
    }
}

// ── Timing engine ─────────────────────────────────────────────────────────────

/**
 * Runs $fn $revs times per iteration, for $iterations iterations (after $warmup
 * warmup rounds), and returns timing statistics in microseconds.
 *
 * @return array{mean_μs:float, stdev_μs:float, min_μs:float, max_μs:float, ops_per_sec:int, revs:int, its:int}
 */
function bench(callable $fn, int $revs = 500, int $iterations = 7, int $warmup = 3): array
{
    for ($w = 0; $w < $warmup; $w++) {
        for ($r = 0; $r < $revs; $r++) {
            $fn();
        }
    }

    $times = [];
    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        for ($r = 0; $r < $revs; $r++) {
            $fn();
        }
        $elapsed = hrtime(true) - $start;
        $times[] = $elapsed / $revs / 1_000; // ns → μs per operation
    }

    sort($times);
    // Drop the single best and worst to reduce noise when iterations ≥ 5
    if ($iterations >= 5) {
        $times = array_slice($times, 1, -1);
    }

    $n    = count($times);
    $mean = array_sum($times) / $n;
    $var  = array_sum(array_map(fn($t) => ($t - $mean) ** 2, $times)) / $n;

    return [
        'mean_μs'    => round($mean, 5),
        'stdev_μs'   => round(sqrt($var), 5),
        'min_μs'     => round(min($times), 5),
        'max_μs'     => round(max($times), 5),
        'ops_per_sec'=> (int) round(1_000_000 / max($mean, 0.000_001)),
        'revs'       => $revs,
        'its'        => $iterations,
    ];
}

// ── Suite loader ───────────────────────────────────────────────────────────────

/** @return array<string, array{fn:callable, revs:int, warmup:int, its:int}> */
function loadSuite(string $file): array
{
    $factory = require $file;
    return $factory();
}

$suiteFiles = [
    'di'       => __DIR__ . '/src/DiContainerBench.php',
    'injector' => __DIR__ . '/src/InjectorBench.php',
    'router'   => __DIR__ . '/src/RouterBench.php',
    'strings'  => __DIR__ . '/src/StringHelperBench.php',
];

if ($suite !== null && !isset($suiteFiles[$suite])) {
    fwrite(STDERR, "Unknown suite '$suite'. Available: " . implode(', ', array_keys($suiteFiles)) . "\n");
    exit(1);
}

$activeSuites = $suite !== null
    ? [$suite => $suiteFiles[$suite]]
    : $suiteFiles;

// ── Run benchmarks ─────────────────────────────────────────────────────────────

$allResults = [];
$errors     = [];

echo "\n";
echo str_pad('', 90, '─') . "\n";
printf("  %-52s %10s %10s %14s\n", 'Benchmark', 'mean μs', 'stdev μs', 'ops/sec');
echo str_pad('', 90, '─') . "\n";

foreach ($activeSuites as $suiteName => $file) {
    echo "  \033[1;36m[$suiteName]\033[0m\n";

    try {
        $subjects = loadSuite($file);
    } catch (\Throwable $e) {
        $errors[] = "$suiteName: " . $e->getMessage();
        echo "  \033[1;31mFailed to load suite: " . $e->getMessage() . "\033[0m\n";
        continue;
    }

    foreach ($subjects as $label => $spec) {
        try {
            $result = bench(
                $spec['fn'],
                $spec['revs']   ?? 500,
                $spec['its']    ?? 7,
                $spec['warmup'] ?? 3,
            );

            $key = "$suiteName::$label";
            $allResults[$key] = $result;

            $opsFormatted = number_format($result['ops_per_sec']);
            printf(
                "    %-50s %10.5f %10.5f %14s\n",
                $label,
                $result['mean_μs'],
                $result['stdev_μs'],
                $opsFormatted,
            );
        } catch (\Throwable $e) {
            $errors[] = "$suiteName::$label: " . $e->getMessage();
            printf("    %-50s \033[1;31mERROR: %s\033[0m\n", $label, $e->getMessage());
        }
    }
    echo "\n";
}

echo str_pad('', 90, '─') . "\n";
echo "  PHP " . PHP_VERSION . " · " . php_uname('s') . ' ' . php_uname('m') . "\n";
echo str_pad('', 90, '─') . "\n\n";

if (!empty($errors)) {
    echo "\033[1;33mWarnings:\033[0m\n";
    foreach ($errors as $e) {
        echo "  • $e\n";
    }
    echo "\n";
}

if (empty($allResults)) {
    echo "No results to record.\n";
    exit(count($errors) > 0 ? 1 : 0);
}

// ── Append to history.json ─────────────────────────────────────────────────────

if ($dryRun) {
    echo "\033[1;33m--dry-run: skipping history.json write.\033[0m\n\n";
    exit(0);
}

$historyFile = __DIR__ . '/results/history.json';

$history = file_exists($historyFile)
    ? (json_decode((string) file_get_contents($historyFile), true) ?? [])
    : [];

if (!isset($history['runs'])) {
    $history = [
        'meta' => [
            'description' => 'Yii3-i performance benchmark history',
            'created'     => date('Y-m-d'),
        ],
        'runs' => [],
    ];
}

// Collect git metadata (gracefully skip if git unavailable)
$devNull    = DIRECTORY_SEPARATOR === '\\' ? '2>nul' : '2>/dev/null';
$commit     = trim((string) shell_exec("git rev-parse --short HEAD $devNull") ?: 'unknown');
$commitMsg  = trim((string) shell_exec("git log -1 --format=\"%s\" $devNull") ?: '');
$branch     = trim((string) shell_exec("git rev-parse --abbrev-ref HEAD $devNull") ?: '');

$run = [
    'id'             => date('c'),
    'date'           => date('Y-m-d'),
    'commit'         => $commit,
    'commit_message' => $commitMsg,
    'branch'         => $branch,
    'php_version'    => PHP_VERSION,
    'os'             => php_uname('s') . ' ' . php_uname('m'),
    'results'        => $allResults,
];

$history['runs'][] = $run;

$json = json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    fwrite(STDERR, "Failed to encode history JSON: " . json_last_error_msg() . "\n");
    exit(1);
}

file_put_contents($historyFile, $json . "\n");
echo "Results appended to benchmarks/results/history.json\n";
echo "Run count: " . count($history['runs']) . "\n\n";
