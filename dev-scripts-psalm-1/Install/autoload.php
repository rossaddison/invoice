<?php

declare(strict_types=1);

/**
 * autoload.php has been Psalm level 1 tested according to setting in psalm.xml at root.
 * i.e on the command line run e.g c:\wamp64\www\invoice>php ./vendor/bin/psalm autoload.php
 *
 * Result:
 * ------------------------------
 *       No errors found!
 * ------------------------------
 *
 * In addition, session related $_ENV variables are saved to server related $_SERVER variables and
 * can be viewed under the application's debug mode menu's FAQ's Php Details? Variables
 */

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    fwrite(
        STDERR,
        "Dependencies not found. Please run 'composer install' in the project directory first.\n"
        . "If Composer is not installed, visit https://getcomposer.org/download/ for instructions.\n",
    );
    exit(1);
}

require_once $autoloadPath;

// Only attempt to use Dotenv if the class exists (in case dependencies are not fully installed)
if (class_exists('Dotenv\Dotenv')) {
    /** @var class-string<\Dotenv\Dotenv> $dotenvClass */
    $dotenvClass = 'Dotenv\Dotenv';
    $dotenv = $dotenvClass::createImmutable(__DIR__);
    $dotenv->load();
} else {
    fwrite(STDERR, "Dotenv not found. Ensure your Composer dependencies are installed.\n");
    exit(1);
}

// Safely parse and mirror important environment variables
$_ENV['YII_ENV'] = isset($_ENV['YII_ENV']) && strlen($_ENV['YII_ENV']) > 0 ? $_ENV['YII_ENV'] : null;
$_SERVER['YII_ENV'] = $_ENV['YII_ENV'];

$_ENV['YII_DEBUG'] = isset($_ENV['YII_DEBUG']) && strlen($_ENV['YII_DEBUG']) > 0
    ? filter_var($_ENV['YII_DEBUG'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
    : false;
$_SERVER['YII_DEBUG'] = $_ENV['YII_DEBUG'];

$_ENV['BUILD_DATABASE'] = isset($_ENV['BUILD_DATABASE']) && strlen($_ENV['BUILD_DATABASE']) > 0
    ? filter_var($_ENV['BUILD_DATABASE'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
    : false;
$_SERVER['BUILD_DATABASE'] = $_ENV['BUILD_DATABASE'];

/**
 * Building the database takes longer than usual and the .env $_ENV['BUILD_DATABASE'] should be set to false afterwards
 * https://stackoverflow.com/questions/3829403/how-to-increase-the-execution-timeout-in-php
 */
if ($_SERVER['BUILD_DATABASE']) {
    ini_set('max_execution_time', '360');
}
