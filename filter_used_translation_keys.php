<?php

/**
 * filter_used_translation_keys.php
 *
 * This script scans the codebase to identify which translation keys from the master translation file
 * (resources/messages/en/app.php) are actually used in the project's PHP source and view files.
 *
 * Features:
 * - Searches for translation key usage in both the src and resources/views directories.
 * - Excludes all files in src/Invoice/Language/English from the scan.
 * - Skips translation keys that begin with 'g.'.
 * - Writes used translation keys to resources/messages/en/app_used.php.
 * - Writes unused translation keys to resources/messages/en/app_not_found.php.
 * - Optionally sorts the output files alphabetically if the '--sort' flag is provided.
 * - Can process a batch of keys at a time (pass batch size as the first argument).
 *
 * Usage:
 *   php filter_used_translation_keys.php [batchSize] [--sort]
 *
 * Example:
 *   php filter_used_translation_keys.php 10 --sort
 *
 * This script is designed to help maintain clean and efficient translation files by tracking which keys are still in use.
 */

$translationFile = __DIR__ . '/resources/messages/en/app.php';
$outputFile = __DIR__ . '/resources/messages/en/app_used.php';
$notFoundFile = __DIR__ . '/resources/messages/en/app_not_found.php';

// ANSI escape code for red
define('RED', "\033[31m");
define('RESET', "\033[0m");

// Optional sort flag: use --sort to sort keys before saving
$sortKeys = false;
foreach ($argv as $arg) {
    if ($arg === '--sort') {
        $sortKeys = true;
        break;
    }
}

$batchSize = 1; // Default
if (isset($argv[1]) && is_numeric($argv[1]) && $argv[1] > 0) {
    $batchSize = (int)$argv[1];
}

if (!file_exists($translationFile)) {
    fwrite(STDERR, "Translation file not found: $translationFile\n");
    exit(1);
}

$translations = include $translationFile;
if (!is_array($translations)) {
    fwrite(STDERR, "Translation file did not return an array.\n");
    exit(1);
}

// Load previously found used keys, if any
$usedKeys = [];
if (file_exists($outputFile)) {
    $usedKeys = include $outputFile;
    if (!is_array($usedKeys)) $usedKeys = [];
}

$notFoundKeys = [];
if (file_exists($notFoundFile)) {
    $notFoundKeys = include $notFoundFile;
    if (!is_array($notFoundKeys)) $notFoundKeys = [];
}

$keys = array_keys($translations);
$remainingKeys = array_diff($keys, array_keys($usedKeys));
$toProcess = array_slice($remainingKeys, 0, $batchSize);

$paths = ['src', 'resources/views'];

foreach ($toProcess as $key) {
    // Skip keys beginning with 'g.'
    if (strpos($key, 'g.') === 0) {
        echo "Skipped key (begins with 'g.'): $key\n";
        continue;
    }

    $found = false;
    $locations = [];
    foreach ($paths as $path) {
        $output = [];
        $grepKey = preg_quote($key, '/');
        // Exclude all files in src/Invoice/Language/English
        $cmd = "grep -r -I -n --include='*.php' --exclude=app.php --exclude-dir='English' \"" . $grepKey . "\" \"$path\"";
        exec($cmd, $output, $return_var);
        if (!empty($output)) {
            $found = true;
            foreach ($output as $line) {
                $parts = explode(':', $line, 3);
                $file = $parts[0];
                $lineNumber = $parts[1];
                $locations[] = "$file:$lineNumber";
            }
            break;
        }
    }
    if ($found) {
        $usedKeys[$key] = $translations[$key];
        // Remove from notFoundKeys if it was previously not found
        if (($nfIdx = array_search($key, $notFoundKeys)) !== false) {
            unset($notFoundKeys[$nfIdx]);
        }
        echo "Processed and added key: $key\n";
        echo "  Found in: " . implode(', ', $locations) . "\n";
    } else {
        $notFoundKeys[] = $key;
        echo RED . "Key not found: $key" . RESET . "\n";
    }
}

// Sort keys alphabetically if --sort is specified
if ($sortKeys) {
    ksort($usedKeys);
    sort($notFoundKeys);
}

file_put_contents($outputFile, "<?php\nreturn " . var_export($usedKeys, true) . ";\n");
file_put_contents($notFoundFile, "<?php\nreturn " . var_export($notFoundKeys, true) . ";\n");