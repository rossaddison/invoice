#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Convenience installer script
 * Allows running: php install_writable.php
 * Application: Checks if yiipath is executable
 */

// Check if Composer is installed and accessible
$composerCheckOutput = [];
$composerCheckStatus = 0;
exec('composer --version 2>&1', $composerCheckOutput, $composerCheckStatus);

if ($composerCheckStatus !== 0 || empty($composerCheckOutput)) {
    fwrite(STDERR, "Composer is not installed or not found in your PATH.\n");
    fwrite(STDERR, "Please install Composer and make sure it is accessible from the command line before running this script.\n");
    exit(1);
}

$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "Composer dependencies not found. Running composer install...\n";

    // Figure out composer command for Windows or Unix
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Try default ComposerSetup path, fallback to just 'composer'
        $defaultComposer = 'C:\ProgramData\ComposerSetup\bin\composer.bat';
        $composerCmd = file_exists($defaultComposer)
            ? "\"$defaultComposer\" install"
            : 'composer install';
    } else {
        $composerCmd = 'composer install';
    }

    echo "Running: $composerCmd\n\n";

    // Stream Composer output live (stdout and stderr)
    $exitCode = 0;
    passthru($composerCmd, $exitCode);

    if (!file_exists($composerAutoload) || $exitCode !== 0) {
        fwrite(STDERR, "Composer install did not complete successfully. Please check for errors above.\n");
        exit(1);
    }
}

// Run Yii installer
$yiiPath = __DIR__ . '/yii';

if (!file_exists($yiiPath)) {
    fwrite(STDERR, "Error: yii console script not found.\n");
    fwrite(STDERR, "Please ensure you are in the correct directory.\n");
    exit(1);
}

// Make sure it's executable (mainly for Unix)
if (!is_executable($yiiPath)) {
    @chmod($yiiPath, 0750);
}

$command = escapeshellarg($yiiPath) . ' install';
echo "Running: {$command}\n\n";

$exitCode = 0;
passthru($command, $exitCode);
exit($exitCode);
