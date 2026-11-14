<?php

declare(strict_types=1);

/**
 * Keeps real people's data out of tests and fixtures. See App\Invoice\Testing\VirtualizeSensitiveTestDataCheck
 * and docs/SENSITIVE_TEST_DATA_GUARD_NOVEMBER_2026.md.
 *
 *   php bin/virtualize-sensitive-test-data-check.php check [--baseline=<file>] <path>...
 *       (A path of `-` reads further paths from stdin, one per line: use it for long lists.)
 *       Reports real-looking emails, telephone numbers, postcodes and titled names in the files given
 *       that are test data (under Tests/ or a Fixtures/ folder). Exit status 1 when any are found, 0
 *       otherwise. Files that are not test data, or not text, are skipped. Never prints the values.
 *       With --baseline, values already counted in the baseline file are tolerated, so only NEW
 *       real-looking data fails; the baseline records per file and rule how many are tolerated.
 *
 *   php bin/virtualize-sensitive-test-data-check.php write-baseline <file> <path>...
 *       Records the current real-looking values of the files given as the tolerated baseline. The
 *       baseline should only ever shrink: tidy a test, regenerate it, commit the smaller file.
 *
 *   php bin/virtualize-sensitive-test-data-check.php virtualize <input> <output>
 *       Writes a copy of <input> with every real-looking value replaced by a virtual one (see the
 *       class docblock), so a real file can become a fixture. Write the output where you intend to
 *       commit it, never next to the original in a folder that is committed.
 *
 * It has no dependencies on purpose: a commit hook or a CI job can run it with a bare PHP.
 */

require_once __DIR__ . '/../src/Invoice/Testing/VirtualizeSensitiveTestDataCheck.php';

use App\Invoice\Testing\VirtualizeSensitiveTestDataCheck;

const TEXT_EXTENSIONS = ['php', 'md', 'txt', 'csv', 'tsv', 'json', 'xml', 'yml', 'yaml', 'html', 'htm', 'ts', 'js', 'sql', 'ini'];
const MAX_BYTES = 2_000_000;

/** Test data is anything under Tests/ or in a folder called Fixtures/fixtures. */
function isTestData(string $path): bool
{
    $normal = str_replace('\\', '/', $path);
    return str_starts_with($normal, 'Tests/')
        || str_contains($normal, '/Tests/')
        || (bool) preg_match('#(^|/)[Ff]ixtures?/#', $normal);
}

function isCheckable(string $path): bool
{
    if (!is_file($path) || filesize($path) > MAX_BYTES) {
        return false;
    }
    return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), TEXT_EXTENSIONS, true);
}

/**
 * @param list<string> $paths
 * @return array<string, array<string, list<array{rule: string, line: int, hint: string}>>> path => rule => findings
 */
function scan(VirtualizeSensitiveTestDataCheck $check, array $paths): array
{
    $result = [];
    foreach ($paths as $path) {
        if (!isTestData($path) || !isCheckable($path)) {
            continue;
        }
        $result[$path] = [];
        foreach ($check->check((string) file_get_contents($path)) as $finding) {
            $result[$path][$finding['rule']][] = $finding;
        }
    }
    return $result;
}

/**
 * A `-` among the paths means "read more paths from stdin, one per line", so a hook or CI job can pass
 * hundreds of files in one process (no argument-length limit, no batching).
 *
 * @param list<string> $paths
 * @return list<string>
 */
function expandPaths(array $paths): array
{
    $expanded = [];
    foreach ($paths as $path) {
        if ($path !== '-') {
            $expanded[] = $path;
            continue;
        }
        foreach (explode("\n", (string) stream_get_contents(STDIN)) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $expanded[] = $line;
            }
        }
    }
    return $expanded;
}

/** @return array<string, array<string, int>> path => rule => tolerated count */
function readBaseline(string $file): array
{
    $baseline = [];
    if (!is_file($file)) {
        return $baseline;
    }
    foreach (explode("\n", (string) file_get_contents($file)) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode("\t", $line);
        if (count($parts) === 3) {
            $baseline[$parts[0]][$parts[1]] = (int) $parts[2];
        }
    }
    return $baseline;
}

$command = $argv[1] ?? '';
$check = new VirtualizeSensitiveTestDataCheck();

if ($command === 'virtualize') {
    $input = $argv[2] ?? '';
    $output = $argv[3] ?? '';
    if ($input === '' || $output === '' || !is_file($input)) {
        fwrite(STDERR, "Usage: virtualize <input> <output>\n");
        exit(2);
    }
    $text = (string) file_get_contents($input);
    $virtual = $check->virtualize($text);
    file_put_contents($output, $virtual);
    fwrite(STDOUT, sprintf(
        "Wrote %s: %d value(s) replaced, %d real-looking value(s) left (street names and other free text are not detected: read the file before committing it).\n",
        $output,
        count($check->check($text)),
        count($check->check($virtual)),
    ));
    exit(0);
}

if ($command === 'write-baseline') {
    $file = $argv[2] ?? '';
    if ($file === '') {
        fwrite(STDERR, "Usage: write-baseline <file> <path>...\n");
        exit(2);
    }
    $scanned = scan($check, expandPaths(array_slice($argv, 3)));
    ksort($scanned);
    $out = "# Real-looking test-data values that existed when the guard was introduced, tolerated per file and\n"
        . "# rule: path<TAB>rule<TAB>count. Only NEW real-looking values fail. This file should only shrink:\n"
        . "# virtualize a test, regenerate with write-baseline, commit the smaller file.\n";
    $entries = 0;
    foreach ($scanned as $path => $byRule) {
        ksort($byRule);
        foreach ($byRule as $rule => $findings) {
            $out .= sprintf("%s\t%s\t%d\n", $path, $rule, count($findings));
            $entries++;
        }
    }
    file_put_contents($file, $out);
    fwrite(STDOUT, sprintf("Wrote %s: %d file/rule entries.\n", $file, $entries));
    exit(0);
}

if ($command !== 'check') {
    fwrite(STDERR, "Usage: check [--baseline=<file>] <path>... | write-baseline <file> <path>... | virtualize <input> <output>\n");
    exit(2);
}

$baseline = [];
$paths = [];
foreach (array_slice($argv, 2) as $argument) {
    if (str_starts_with($argument, '--baseline=')) {
        $baseline = readBaseline(substr($argument, strlen('--baseline=')));
        continue;
    }
    $paths[] = $argument;
}

$scanned = scan($check, expandPaths($paths));
$total = 0;
foreach ($scanned as $path => $byRule) {
    foreach ($byRule as $rule => $findings) {
        $tolerated = $baseline[$path][$rule] ?? 0;
        if (count($findings) <= $tolerated) {
            continue;
        }
        foreach ($findings as $finding) {
            $total++;
            fwrite(STDOUT, sprintf("%s:%d  real-looking %s (%s)\n", $path, $finding['line'], $rule, $finding['hint']));
        }
    }
}

if ($total > 0) {
    fwrite(STDERR, sprintf(
        "\n%d real-looking value(s) in test data (more than the baseline tolerates). Test data must be virtual:\n"
        . "use the replacements in App\\Invoice\\Testing\\VirtualizeSensitiveTestDataCheck (emails @example.test,\n"
        . "phones 01632 960xxx, postcodes ZZ1 1AA), or run:\n"
        . "  php bin/virtualize-sensitive-test-data-check.php virtualize <input> <output>\n"
        . "A line that needs a real-looking value on purpose can carry the marker: %s\n",
        $total,
        VirtualizeSensitiveTestDataCheck::IGNORE_MARKER,
    ));
    exit(1);
}

fwrite(STDOUT, sprintf("Checked %d test-data file(s): no new real-looking personal data.\n", count($scanned)));
exit(0);
