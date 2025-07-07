<?php

declare(strict_types=1);

/**
 * Script to remove orig_app.php files from all language folders (except 'en') in resources/messages/.
 *
 * Usage:
 *   Place this script at the project root and run:
 *     php remove_orig_app_files.php
 *
 * This script helps keep your translation directories clean after migrating or renaming translation files.
 */

$baseDir = __DIR__ . '/resources/messages/';

foreach (glob($baseDir . '*', GLOB_ONLYDIR) as $folder) {
    $lang = basename($folder);
    if ($lang === 'en') {
        continue;
    }

    $file = $folder . '/orig_app.php';
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "Deleted $file\n";
        } else {
            echo "Failed to delete $file\n";
        }
    }
}

echo "Cleanup complete. All orig_app.php files (except in 'en') have been removed.\n";
