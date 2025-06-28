<?php

declare(strict_types=1);
/**
 * This script copies resources/messages/en/app.php to resources/messages/en/app_dot.php,
 * replacing all underscores in the array keys with dots.
 *
 * Usage:
 *   Place this script at the project root and run:
 *     php copy_appphp_underscore_to_dot.php
 *
 * This helps in migrating translation keys from underscore_format to dot.format style.
 */

$sourceFile = __DIR__ . '/resources/messages/en/app.php';
$destFile = __DIR__ . '/resources/messages/en/app_dot.php';

if (!file_exists($sourceFile)) {
    fwrite(STDERR, "Source file not found: $sourceFile\n");
    exit(1);
}

$original = include $sourceFile;
if (!is_array($original)) {
    fwrite(STDERR, "Source file did not return an array.\n");
    exit(1);
}

// Replace underscores with dots in all keys (top-level only)
$dotKeys = [];
foreach ($original as $key => $value) {
    $dotKey = str_replace('_', '.', $key);
    $dotKeys[$dotKey] = $value;
}

file_put_contents($destFile, "<?php\nreturn " . var_export($dotKeys, true) . ";\n");

echo "Created $destFile with dot keys.\n";
