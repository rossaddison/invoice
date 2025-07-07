<?php

declare(strict_types=1);
/**
 * For each key from app_sorted.php, search for any translate('...') or translate("...") in code
 * where the quoted key contains the current key as a substring (partial match, not exact match).
 * Prompt to replace the full quoted key with the current key.
 * Skips resources/messages/ and src/Invoice/Language/English/ folders.
 * Usage:
 *   php interactive_translate_key_replace_partial.php
 */

$sortedFile = __DIR__ . '/resources/messages/en/app_sorted.php';
$searchDirs = [__DIR__ . '/src', __DIR__ . '/resources'];

function normalize_path($path)
{
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

$excludePaths = [
    normalize_path(__DIR__ . '/resources/messages/'),
    normalize_path(__DIR__ . '/src/Invoice/Language/English/'),
];

if (!file_exists($sortedFile)) {
    fwrite(STDERR, "Sorted keys file not found: $sortedFile\n");
    exit(1);
}
$keys = array_keys(include $sortedFile);

function prompt($message)
{
    echo $message . ' (yes/no): ';
    $handle = fopen('php://stdin', 'r');
    $line = fgets($handle);
    return trim(strtolower($line));
}

foreach ($keys as $key) {
    foreach ($searchDirs as $dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $filePath = $file->getPathname();
            $normalizedPath = normalize_path($filePath);

            // Exclude files in any of the excluded paths
            foreach ($excludePaths as $exclude) {
                if (strpos($normalizedPath, $exclude) === 0) {
                    continue 2;
                }
            }

            if (!in_array($file->getExtension(), ['php'])) {
                continue;
            }

            $contents = file_get_contents($filePath);

            // Find all translate('...') or translate("...")
            $regex = '/translate\((["\'])(.*?)\1\)/';

            $modified = false;
            if (preg_match_all($regex, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                // $matches[2] contains the keys inside translate()
                // Go backwards to avoid offset issues when replacing in-place
                for ($idx = count($matches[2]) - 1; $idx >= 0; $idx--) {
                    $foundKey = $matches[2][$idx][0];
                    $offset = $matches[0][$idx][1]; // full match offset

                    // Only prompt if the found key contains the current $key as a substring and is not already exactly $key
                    if ($foundKey !== $key && stripos($foundKey, $key) !== false) {
                        $fullMatch = $matches[0][$idx][0];
                        // Show context
                        $start = max(0, $offset - 20);
                        $context = substr($contents, $start, strlen($fullMatch) + 40);
                        echo "File: $filePath\n";
                        echo 'Context: ...' . str_replace("\n", '\\n', $context) . "...\n";
                        echo "Found translate('$foundKey'), replace with translate('$key')?\n";
                        $answer = prompt("Replace \"$foundKey\" with \"$key\" inside translate()?");

                        if ($answer === 'yes' || $answer === 'y') {
                            // Replace this instance only
                            $quote = $matches[1][$idx][0];
                            $search = "translate($quote$foundKey$quote)";
                            $replace = "translate($quote$key$quote)";
                            $contents = substr_replace(
                                $contents,
                                $replace,
                                $offset,
                                strlen($search)
                            );
                            $modified = true;
                            echo "Replaced in $filePath\n";
                        } else {
                            echo "Skipped in $filePath\n";
                        }
                    }
                }
            }
            if ($modified) {
                file_put_contents($filePath, $contents);
            }
        }
    }
}
echo "Done.\n";
