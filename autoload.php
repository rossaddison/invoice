<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$_ENV['YII_ENV'] = strlen(($_ENV['YII_ENV'])) == 0 ? null : $_ENV['YII_ENV'];
$_SERVER['YII_ENV'] = $_ENV['YII_ENV'];

$_ENV['YII_DEBUG'] = strlen(($_ENV['YII_DEBUG'])) == 0 ? false : true;
$_SERVER['YII_DEBUG'] = $_ENV['YII_DEBUG'];
