<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\StatementInterface;

/**
 * Generates a gzip-compressed SQL dump (schema + data) of every table in the
 * default database, entirely through Cycle ORM's own DBAL — no mysqldump
 * binary, no shell_exec(). Keeps the feature portable to shared hosting where
 * exec()-family functions are commonly disabled.
 *
 * Rows are streamed and written in batches rather than buffered in memory, so
 * dump size isn't bounded by available RAM.
 */
final class DatabaseBackupService
{
    private const INSERT_BATCH_SIZE = 500;

    public function __construct(
        private readonly DatabaseInterface $database,
    ) {
    }

    public function writeGzippedDump(string $filePath): void
    {
        $gz = gzopen($filePath, 'wb9');
        if ($gz === false) {
            throw new DatabaseBackupException('Unable to open backup file for writing: ' . $filePath);
        }
        try {
            $driver = $this->database->getDriver();

            gzwrite($gz, "-- Invoice database backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($this->database->getTables() as $table) {
                $tableName = $table->getName();
                $quotedTable = self::quoteIdentifier($tableName);

                gzwrite($gz, "-- --------------------------------------------------------\n");
                gzwrite($gz, "-- Table: {$tableName}\n");
                gzwrite($gz, "-- --------------------------------------------------------\n\n");
                gzwrite($gz, "DROP TABLE IF EXISTS {$quotedTable};\n");
                $this->writeCreateTable($gz, $quotedTable);
                $this->writeTableData($gz, $driver, $quotedTable);
                gzwrite($gz, "\n");
            }

            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            gzclose($gz);
        }
    }

    /**
     * @param resource $gz
     */
    private function writeCreateTable($gz, string $quotedTable): void
    {
        /** @var list<string>|false $createRow */
        $createRow = $this->database->query("SHOW CREATE TABLE {$quotedTable}")
            ->fetch(StatementInterface::FETCH_NUM);
        if (is_array($createRow) && isset($createRow[1])) {
            gzwrite($gz, $createRow[1] . ";\n\n");
        }
    }

    /**
     * @param resource $gz
     */
    private function writeTableData($gz, DriverInterface $driver, string $quotedTable): void
    {
        $rows = $this->database->query("SELECT * FROM {$quotedTable}");
        /** @var list<string> $batch */
        $batch = [];
        /** @var array<array-key, string|int|float|null> $row */
        foreach ($rows as $row) {
            $values = array_map(
                static fn (string|int|float|null $value): string => $value === null
                    ? 'NULL'
                    : $driver->quote((string) $value),
                $row,
            );
            $batch[] = '(' . implode(',', $values) . ')';
            if (count($batch) >= self::INSERT_BATCH_SIZE) {
                gzwrite($gz, "INSERT INTO {$quotedTable} VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if ($batch !== []) {
            gzwrite($gz, "INSERT INTO {$quotedTable} VALUES\n" . implode(",\n", $batch) . ";\n");
        }
    }

    private static function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
