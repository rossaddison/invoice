<?php

declare(strict_types=1);

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;

$env = $_ENV['APP_ENV'] ?? 'local';
$dbUser = ($_ENV['DB_USERNAME'] ?? '') ?: 'root';
$dbName = ($_ENV['DB_NAME'] ?? '') ?: 'yii3_i';
$dbPassword = ($_ENV['DB_PASSWORD'] ?? '') ?: '';

switch ($env) {
    case 'docker':
        $dbHost = $_ENV['DB_HOST_IP_ADDRESS'] ?? '192.168.0.24';
        break;
    default:
        $dbHost = $_ENV['DB_HOST_IP_ADDRESS'] ?? 'localhost';
}

$dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName;

return [
    ConnectionInterface::class => static function (SchemaCache $schemaCache)
        use ($dsn, $dbUser, $dbPassword): Connection {
        return new Connection(
            new Driver($dsn, $dbUser, $dbPassword),
            $schemaCache,
        );
    },
];
