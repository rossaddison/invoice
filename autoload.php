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
 */

use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$_ENV['YII_ENV'] = strlen($_ENV['YII_ENV']) == 0 ? null : $_ENV['YII_ENV'];
$_SERVER['YII_ENV'] = $_ENV['YII_ENV'];

$_ENV['YII_DEBUG'] = (strlen($_ENV['YII_DEBUG']) == 0
        ? false
        : filter_var($_ENV['YII_DEBUG'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
$_SERVER['YII_DEBUG'] = $_ENV['YII_DEBUG'];


$_ENV['BUILD_DATABASE'] = strlen($_ENV['BUILD_DATABASE']) == 0
        ? false
        : filter_var($_ENV['BUILD_DATABASE'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
$_SERVER['BUILD_DATABASE'] = $_ENV['BUILD_DATABASE'];

/**
 * Building the database takes longer than usual and the .env $_ENV['BUILD_DATABASE'] should be set to false afterwards
 * https://stackoverflow.com/questions/3829403/how-to-increase-the-execution-timeout-in-php
 */
$buildDatabase = $_SERVER['BUILD_DATABASE'];
$buildDatabase ? ini_set('max_execution_time', 360) : '';
