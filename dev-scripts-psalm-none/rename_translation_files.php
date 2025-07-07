<?php

declare(strict_types=1);
/**
 * Script to rename language files in each language folder (except 'en') in resources/messages/:
 * - Renames app.php to orig_app.php (if present)
 * - Renames 1_app.php to app.php (if present)
 *
 * Usage:
 *   Place this script at the project root and run:
 *     php rename_translation_files.php
 */

$baseDir = __DIR__ . '/resources/messages/';

foreach (glob($baseDir . '*', GLOB_ONLYDIR) as $folder) {
    $lang = basename($folder);
    if ($lang === 'en') {
        continue;
    }

    $appFile = $folder . '/app.php';
    $origFile = $folder . '/orig_app.php';
    $newFile = $folder . '/1_app.php';

    // Rename app.php to orig_app.php, only if app.php exists and orig_app.php does not
    if (file_exists($appFile) && !file_exists($origFile)) {
        if (rename($appFile, $origFile)) {
            echo "Renamed $appFile to $origFile\n";
        } else {
            echo "Failed to rename $appFile to $origFile\n";
        }
    }

    // Rename 1_app.php to app.php, only if 1_app.php exists
    if (file_exists($newFile)) {
        if (rename($newFile, $appFile)) {
            echo "Renamed $newFile to $appFile\n";
        } else {
            echo "Failed to rename $newFile to $appFile\n";
        }
    }
}

echo "Renaming complete.\n";
