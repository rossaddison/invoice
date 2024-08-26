<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Avoiding Netbeans message: Do not access Super Globals directly
 * If you are on another development platform you may prefer autoload_wo_filter_input.php
 * FILTER_DEFAULT implies no filtering of string is happening according to e.g. FILTER_SANITIZE_SPECIAL_CHARS
 * e.g $_ENV['YII_DEBUG'] represented as filter_input(INPUT_ENV, 'YII_DEBUG', FILTER_DEFAULT)
 * e.g $_SERVER['YII_DEBUG'] represented as filter_input(INPUT_SERVER, 'YII_DEBUG', FILTER_DEFAULT)
 * Possible return values of filter_input: null if variable not set, false if filter fails, and the value of the variable if it passes
 * @see {root} .env file for environment variables
 * @see https://www.php.net/manual/en/function.filter-input.php
 * @see https://stackoverflow.com/questions/19767894/warning-do-not-access-superglobal-post-array-directly-on-netbeans-7-4-for-ph
 */

$filterYiiEnv = filter_input(INPUT_ENV, 'YII_ENV', FILTER_DEFAULT);
$yiiEnv = (null!==$filterYiiEnv && $filterYiiEnv !== false) ? $filterYiiEnv : '';
$_SERVER['YII_ENV'] = $yiiEnv;

$filterYiiDebug = filter_input(INPUT_ENV, 'YII_DEBUG', FILTER_DEFAULT);
$yiiDebug = filter_var(
    // $filterYiiDebug: variable has been set (i.e. variable not null). If the filter fails allow $filterYiiDebug to be false.      
    null!==$filterYiiDebug ? $filterYiiDebug : true,
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE
) ?? true;
$_SERVER['YII_DEBUG'] = $yiiDebug;
