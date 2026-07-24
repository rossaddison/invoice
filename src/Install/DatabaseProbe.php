<?php

declare(strict_types=1);

namespace App\Install;

use PDO;
use PDOException;

/**
 * Raw-PDO database checks used by the /install wizard. Deliberately avoids
 * Cycle ORM/DI entirely — the wizard must be able to probe/report on a
 * database that may not be configured, reachable, or built yet, before any
 * DI-resolved, ORM-backed service could safely be constructed.
 */
final class DatabaseProbe
{
    public function connectToServer(string $host, string $user, ?string $password): PDO
    {
        $pdo = new PDO(sprintf('mysql:host=%s', $host), $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public function connectToDatabase(string $host, string $name, string $user, ?string $password): PDO
    {
        $pdo = new PDO(sprintf('mysql:host=%s;dbname=%s', $host, $name), $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public function databaseExists(PDO $serverPdo, string $name): bool
    {
        $stmt = $serverPdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
        $stmt->execute([$name]);
        return $stmt->fetch() !== false;
    }

    public function createDatabaseIfMissing(PDO $serverPdo, string $name): void
    {
        if ($this->databaseExists($serverPdo, $name)) {
            return;
        }
        $serverPdo->exec(sprintf(
            'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '', $name),
        ));
    }

    public function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare(
            'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        );
        $stmt->execute([$tableName]);
        return $stmt->fetch() !== false;
    }

    public function countRows(PDO $pdo, string $tableName): int
    {
        $stmt = $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '', $tableName) . '`');
        if ($stmt === false) {
            return 0;
        }
        /** @var int|numeric-string $count */
        $count = $stmt->fetchColumn();
        return (int) $count;
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function testAndCreate(string $host, string $name, string $user, ?string $password): array
    {
        try {
            $serverPdo = $this->connectToServer($host, $user, $password);
            $this->createDatabaseIfMissing($serverPdo, $name);
            return ['success' => true, 'message' => 'Connected — database "' . $name . '" is ready.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
