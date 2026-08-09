<?php

declare(strict_types=1);

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;

$dbUser = ($_ENV['DB_USERNAME'] ?? '') ?: 'root';
$dbName = ($_ENV['DB_NAME'] ?? '') ?: 'yii3_i';
$dbPassword = ($_ENV['DB_PASSWORD'] ?? '') ?: '';

$dbHost = $_ENV['DB_HOST_IP_ADDRESS'] ?? 'localhost';

// charset=utf8mb4 must be explicit -- PDO/mysqlnd's compiled-in default
// charset is negotiated per-connection independent of the database's
// and every column's own utf8mb4 declaration, and silently falls short
// of it (e.g. 3-byte utf8) unless told otherwise here. Confirmed live in
// production: a genuine 4-byte emoji (see PaymentRecordChannel::emoji())
// written to the already-utf8mb4 payment.note column threw MySQL 1366
// "Incorrect string value" purely because this DSN never requested
// utf8mb4 for the connection itself. See
// docs/MYSQL_CONNECTION_CHARSET_BUG_AUGUST_2026.md.
$dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';

return [
    ConnectionInterface::class => static function (SchemaCache $schemaCache)
        use ($dsn, $dbUser, $dbPassword): Connection {
        return new Connection(
            new Driver($dsn, $dbUser, $dbPassword),
            $schemaCache,
        );
    },
];
