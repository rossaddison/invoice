<?php

declare(strict_types=1);

use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

if (is_bool(getenv('YII_C3'))) {
    $c3 = dirname(__DIR__) . '/c3.php';
    if (file_exists($c3)) {
        require_once $c3;
    }
}

    // PHP built-in server routing.
if (PHP_SAPI === 'cli-server') {
    // Serve static files as is.
    /** @psalm-suppress MixedArgument */
    $path = parse_url(filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_DEFAULT) ?? '', PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false;
    }

    // Explicitly set for URLs with dot.
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/autoload.php';

/**
 * Avoiding Netbeans message: Do not access Super Globals directly
 * FILTER_DEFAULT implies no filtering of string is happening according to e.g. FILTER_SANITIZE_SPECIAL_CHARS
 * e.g $_ENV['YII_DEBUG'] represented as filter_input(INPUT_ENV, 'YII_DEBUG', FILTER_DEFAULT)
 * Possible return values of filter_input: null if variable not set, false if filter fails, and the value of the variable if it passes
 * @see {root} .env file for environment variables
 * @see https://www.php.net/manual/en/function.filter-input.php
 * @see https://stackoverflow.com/questions/19767894/warning-do-not-access-superglobal-post-array-directly-on-netbeans-7-4-for-ph
 */

$filterYiiEnv = filter_input(INPUT_ENV, 'YII_ENV', FILTER_DEFAULT);

// if the variable has been set (i.e variable not null) and the filter has not failed (not false)
$yiiEnv = (null!==$filterYiiEnv && $filterYiiEnv !== false) ? $filterYiiEnv : '';

$filterYiiDebug = filter_input(INPUT_ENV, 'YII_DEBUG', FILTER_DEFAULT);
$yiiDebug = filter_var(
    // $filterYiiDebug: variable has been set (i.e. variable not null). If the filter fails allow $filterYiiDebug to be false.    
    null!==$filterYiiDebug ? $filterYiiDebug : true,
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE
) ?? true;

// Run HTTP application runner
$runner = new HttpApplicationRunner(
    rootPath: dirname(__DIR__),
    // e.g. true or false    
    debug: $yiiDebug,
    // e.g. true or false    
    checkEvents: $yiiDebug,
    // e.g. 'prod' or 'dev' or ''    
    environment: $yiiEnv  
);
$runner->run();