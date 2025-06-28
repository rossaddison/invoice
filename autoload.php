<?php

declare(strict_types=1);

/**
 * autoload.php has been Psalm level 1 tested according to setting in psalm.xml at root.
 * i.e on the command line run e.g c:\wamp64\wwww\invoice>php ./vendor/bin/psalm autoload.php
 *
 * Result:
 *
 * Target PHP version: 8.3 (inferred from composer.json) Enabled extensions: dom (unsupported extensions: fileinfo, pdo_sqlite).
 * Scanning files...
 * Analyzing files...
 *
 * ░
 * ------------------------------
 *
 *       No errors found!
 *
 * ------------------------------
 *
 * Checks took 23.69 seconds and used 160.243MB of memory
 * Psalm was able to infer types for 100% of the codebase
 *
 * In addition, session related $_ENV variables are saved to server related $_SERVER variables and
 * can be viewed under the application's debug mode menu's FAQ's Php Details? Variables
 *
 * This file is designed to be robust to missing dependencies and environment variables.
 * It gracefully handles missing vendor/autoload.php and missing .env files.
 */

/**
 * Safely require Composer's autoloader, but only if it exists.
 * If vendor/autoload.php doesn't exist, print an error message and exit gracefully.
 */
$vendorAutoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($vendorAutoloadPath)) {
    echo "Error: Composer dependencies are not installed.\n";
    echo "Please run 'composer install' to install the required dependencies.\n";
    echo "If Composer is not installed, please install it first from https://getcomposer.org/\n";
    exit(1);
}

require_once $vendorAutoloadPath;

use Dotenv\Dotenv;

/**
 * Load .env variables using vlucas/phpdotenv, but handle missing .env file gracefully.
 * If .env doesn't exist, continue without loading environment variables.
 */
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    try {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    } catch (Exception $e) {
        // If .env file exists but has issues, log the error but continue
        error_log("Warning: Could not load .env file: " . $e->getMessage());
    }
}

/**
 * Safely process environment variables with robust checks for missing values.
 * Treat empty or missing values appropriately and use filter_var for boolean parsing.
 */

/**
 * Process YII_ENV: Set to null if empty or missing
 */
$yiiEnv = $_ENV['YII_ENV'] ?? '';
$_ENV['YII_ENV'] = (empty(trim($yiiEnv)) ? null : trim($yiiEnv));
$_SERVER['YII_ENV'] = $_ENV['YII_ENV'];

/**
 * Process YII_DEBUG: Use filter_var for boolean parsing, default to false for empty/missing values
 */
$yiiDebug = $_ENV['YII_DEBUG'] ?? '';
$_ENV['YII_DEBUG'] = (empty(trim($yiiDebug)) 
    ? false 
    : filter_var(trim($yiiDebug), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
$_SERVER['YII_DEBUG'] = $_ENV['YII_DEBUG'];

/**
 * Process BUILD_DATABASE: Use filter_var for boolean parsing, default to false for empty/missing values
 */
$buildDatabaseEnv = $_ENV['BUILD_DATABASE'] ?? '';
$_ENV['BUILD_DATABASE'] = (empty(trim($buildDatabaseEnv)) 
    ? false 
    : filter_var(trim($buildDatabaseEnv), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
$_SERVER['BUILD_DATABASE'] = $_ENV['BUILD_DATABASE'];

/**
 * Building the database takes longer than usual and the .env $_ENV['BUILD_DATABASE'] should be set to false afterwards
 * https://stackoverflow.com/questions/3829403/how-to-increase-the-execution-timeout-in-php
 * 
 * If BUILD_DATABASE is true, set max_execution_time to 360 seconds.
 */
$buildDatabase = $_SERVER['BUILD_DATABASE'];
if ($buildDatabase === true) {
    ini_set('max_execution_time', '360');
}
