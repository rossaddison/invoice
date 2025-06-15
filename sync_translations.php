<?php
/**
 * Script to synchronize translation keys in app.php files across all language folders (except 'en')
 * with the master English translation (resources/messages/en/app.php).
 *
 * - Existing translations are preserved where possible.
 * - Extra keys not present in the master are removed.
 * - Missing keys are filled with the English value from the master.
 * - Instead of overwriting the original app.php in each language folder, a new file named 1_app.php is created.
 *
 * Usage:
 *   Place this script at the project root and run:
 *     php sync_translations.php
 *
 * Shared as part of the GitHub Copilot Chat conversation for the invoice project.
 * https://github.com/rossaddison/invoice
 */

$baseDir = __DIR__ . '/resources/messages/';
$masterFile = $baseDir . 'en/app.php';

// Load the master English translations
if (!file_exists($masterFile)) {
    fwrite(STDERR, "Master file not found: $masterFile\n");
    exit(1);
}
$master = include $masterFile;
if (!is_array($master)) {
    fwrite(STDERR, "Master file did not return an array.\n");
    exit(1);
}

// Loop through each language directory except 'en'
foreach (glob($baseDir . '*', GLOB_ONLYDIR) as $folder) {
    $lang = basename($folder);
    if ($lang === 'en') continue;

    $file = $folder . '/app.php';
    $outputFile = $folder . '/1_app.php';

    if (file_exists($file)) {
        $existing = include $file;
        if (!is_array($existing)) $existing = [];

        // Build new array: preserve translations, fill missing with English, remove extras
        $new = [];
        foreach ($master as $key => $val) {
            $new[$key] = array_key_exists($key, $existing) ? $existing[$key] : $val;
        }

        // Write the synchronized array to 1_app.php (do not overwrite app.php)
        file_put_contents($outputFile, "<?php\nreturn " . var_export($new, true) . ";\n");
        echo "Created $outputFile\n";
    } else {
        // If the file does not exist, create 1_app.php from the master
        file_put_contents($outputFile, "<?php\nreturn " . var_export($master, true) . ";\n");
        echo "Created $outputFile (copied from master)\n";
    }
}
echo "All 1_app.php files have been synchronized with the master (original app.php preserved).\n";