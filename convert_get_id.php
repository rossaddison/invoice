#!/usr/bin/env php
<?php

/**
 * Converts ->getId() to ->reqId() in the caller files listed in the
 * entity_to_infrastructure.php registry for a given entity.
 *
 * Usage:
 *   php convert_get_id.php {EntityName} [--dry-run]
 *
 * Examples:
 *   php convert_get_id.php CategorySecondary --dry-run
 *   php convert_get_id.php CategorySecondary
 *
 * --dry-run  shows every line that would change without writing files.
 *
 * WARNING: Replaces ALL ->getId() occurrences in each listed caller
 * file. If a caller file references more than one entity type each
 * with its own getId(), review the diff carefully before applying.
 * Always run vendor/bin/psalm after applying.
 */

declare(strict_types=1);

$entityName = null;
$dryRun     = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    } else {
        $entityName = $arg;
    }
}

if ($entityName === null) {
    echo "Usage: php convert_get_id.php {EntityName} [--dry-run]\n";
    exit(1);
}

$registryPath = __DIR__
    . '/src/Infrastructure/entity_to_infrastructure.php';

if (!file_exists($registryPath)) {
    echo "Registry not found: {$registryPath}\n";
    exit(1);
}

$registry = require $registryPath;

if (!array_key_exists($entityName, $registry)) {
    echo "'{$entityName}' not found in registry.\n";
    exit(1);
}

$entry = $registry[$entityName];

if ($entry === null) {
    echo "'{$entityName}' is pending — infrastructure class not yet"
        . " created.\n";
    exit(1);
}

if (($entry['req_id'] ?? false) === false) {
    echo "WARNING: '{$entityName}' has req_id => false in the registry."
        . " Complete the reqId() refactor on the infrastructure class"
        . " before converting callers.\n";
    exit(1);
}

$callers = $entry['callers'] ?? [];

if (empty($callers)) {
    echo "No callers listed for '{$entityName}'. Nothing to do.\n";
    exit(0);
}

$mode    = $dryRun ? '[DRY RUN] ' : '';
$changed = 0;
$pattern = '/->getId\(\)/';

foreach ($callers as $relativePath) {
    $fullPath = __DIR__ . '/' . $relativePath;

    if (!file_exists($fullPath)) {
        echo "{$mode}SKIP (not found): {$relativePath}\n";
        continue;
    }

    $lines   = file($fullPath);
    $updated = [];
    $hits    = [];

    foreach ($lines as $n => $line) {
        $new = preg_replace($pattern, '->reqId()', $line);
        $updated[] = $new;
        if ($new !== $line) {
            $hits[] = sprintf(
                '  line %d: %s       => %s',
                $n + 1,
                rtrim($line),
                rtrim($new),
            );
        }
    }

    if (empty($hits)) {
        echo "{$mode}NO CHANGE: {$relativePath}\n";
        continue;
    }

    echo "{$mode}UPDATING: {$relativePath}\n";
    foreach ($hits as $hit) {
        echo $hit . "\n";
    }

    if (!$dryRun) {
        file_put_contents($fullPath, implode('', $updated));
    }

    $changed++;
}

echo "\n{$mode}Done. {$changed} file(s) "
    . ($dryRun ? 'would be ' : '')
    . "modified.\n";

if (!$dryRun && $changed > 0) {
    echo "Run vendor/bin/psalm to verify no regressions.\n";
}
