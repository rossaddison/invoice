<?php
/**
 * Simple Translation Key Usage Reporter
 *
 * Prompts for translation directory (with app.php), and codebase directories to scan.
 * For each key in app.php, reports where it is first found in codebase (excluding app.php itself).
 * Prints summary to STDOUT only.
 */

function prompt($msg, $default = null) {
    echo $msg;
    if ($default !== null) echo " [$default]";
    echo ": ";
    $input = trim(fgets(STDIN));
    return $input === '' && $default !== null ? $default : $input;
}

date_default_timezone_set('UTC');

// 1. Get translation directory and check app.php
$baseDir = prompt("Enter translation directory", "resources/messages/en");
$baseDir = rtrim($baseDir, "/\\") . '/';
$translationFile = $baseDir . "app.php";
if (!file_exists($translationFile)) {
    echo "ERROR: $translationFile not found.\n";
    exit(1);
}
echo "Using translations: $translationFile\n";

// 2. Get scan folders (comma-separated, or use default)
$defaultScanFolders = [
    "src/Invoice/",
    "resources/views/invoice/",
    "resources/views/auth/",
    "resources/views/changepassword/",
    "resources/views/forgotpassword/",
    "resources/views/resetpassword/",
    "resources/views/signup/",
    "resources/views/site/",
    "resources/views/user/",
];
$scanInput = prompt(
    "Enter code directories to scan (comma-separated, empty for default list)", 
    implode(",", $defaultScanFolders)
);
$scanFolders = array_filter(array_map('trim', explode(",", $scanInput)));
$existingFolders = [];
foreach ($scanFolders as $folder) {
    if (is_dir($folder)) {
        $existingFolders[] = $folder;
    } else {
        echo "Warning: Directory not found, skipping: $folder\n";
    }
}
if (empty($existingFolders)) {
    echo "No scan folders to process. Exiting.\n";
    exit(1);
}

// 3. Load all keys
$allKeys = include $translationFile;
if (!is_array($allKeys)) {
    echo "ERROR: $translationFile must return an array.\n";
    exit(1);
}

// 4. For each key, grep in codebase (excluding app.php itself)
foreach ($allKeys as $key => $value) {
    $found = false;
    foreach ($existingFolders as $folder) {
        $results = [];
        exec("grep -rl --include='*.php' " . escapeshellarg($key) . " $folder 2>/dev/null", $results);
        foreach ($results as $result) {
            if (realpath($result) === realpath($translationFile)) continue; // skip app.php itself
            // Report and break at first found instance
            printf("Key: '%s' found in: %s (under %s)\n", $key, $result, $folder);
            $found = true;
            break 2;
        }
    }
    if (!$found) {
        printf("Key: '%s' NOT found in codebase.\n", $key);
    }
}
echo "Done.\n";