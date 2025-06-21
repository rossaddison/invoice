<?php
/**
 * This script sorts the associative array in resources/messages/en/app.php by key (alphabetically).
 * The sorted array is saved as resources/messages/en/app_sorted.php.
 *
 * Usage:
 *   php sort_app_php_by_key.php
 */

$sourceFile = __DIR__ . '/resources/messages/en/app.php';
$destFile   = __DIR__ . '/resources/messages/en/app_sorted.php';

if (!file_exists($sourceFile)) {
    fwrite(STDERR, "Source file not found: $sourceFile\n");
    exit(1);
}

$translations = include $sourceFile;
if (!is_array($translations)) {
    fwrite(STDERR, "Source file did not return an array.\n");
    exit(1);
}

// Sort by key
ksort($translations);

// Write sorted array to new file
file_put_contents($destFile, "<?php\nreturn " . var_export($translations, true) . ";\n");

echo "Sorted array written to $destFile\n";