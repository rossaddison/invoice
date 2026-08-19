<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Invoice\Setting\DatabaseBackupService;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\StatementInterface;
use Cycle\Database\TableInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Minimal concrete StatementInterface — Mockery can't auto-mock it since the
 * interface extends \Traversable, and PHP requires any class implementing
 * Traversable to also implement Iterator/IteratorAggregate directly.
 *
 * @implements \IteratorAggregate<int, array<string, string|int|float|null>>
 */
final class FakeStatement implements StatementInterface, \IteratorAggregate
{
    /**
     * @param list<string>|false $fetchResult
     * @param list<array<string, string|int|float|null>> $rows
     */
    public function __construct(
        private readonly array|false $fetchResult = false,
        private readonly array $rows = [],
    ) {
    }

    #[\Override]
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->rows);
    }

    #[\Override]
    public function getQueryString(): string
    {
        return 'SELECT 1';
    }

    #[\Override]
    public function fetch(int $mode = self::FETCH_ASSOC): mixed
    {
        return $this->fetchResult;
    }

    #[\Override]
    public function fetchColumn(?int $columnNumber = null): mixed
    {
        return null;
    }

    #[\Override]
    public function fetchAll(int $mode = self::FETCH_ASSOC): array
    {
        return $this->rows;
    }

    #[\Override]
    public function rowCount(): int
    {
        return count($this->rows);
    }

    #[\Override]
    public function columnCount(): int
    {
        return 0;
    }

    #[\Override]
    public function close(): void
    {
    }
}

/**
 * Covers DatabaseBackupService::writeGzippedDump: schema + data emitted per
 * table, NULL handled distinctly from quoted values, foreign-key-check
 * toggling wraps the whole dump.
 */
#[Test]
final class DatabaseBackupServiceTest
{
    public function writeGzippedDumpProducesExpectedSql(): void
    {
        /** @var TableInterface&m\MockInterface $table */
        $table = m::mock(TableInterface::class);
        $getName = $table->expects('getName');
        $getName->andReturn('widgets');

        /** @var DriverInterface&m\MockInterface $driver */
        $driver = m::mock(DriverInterface::class);
        $quote = $driver->expects('quote');
        $quote->andReturnUsing(static fn (mixed $value): string => "'" . addslashes((string) $value) . "'");
        $quote->times(3);

        $showCreateStatement = new FakeStatement(
            fetchResult: ['widgets', 'CREATE TABLE `widgets` (`id` int NOT NULL)'],
        );
        $selectStatement = new FakeStatement(rows: [
            ['id' => 1, 'name' => 'Widget A'],
            ['id' => 2, 'name' => null],
        ]);

        /** @var DatabaseInterface&m\MockInterface $database */
        $database = m::mock(DatabaseInterface::class);
        $getDriver = $database->expects('getDriver');
        $getDriver->andReturn($driver);
        $getTables = $database->expects('getTables');
        $getTables->andReturn([$table]);
        $query = $database->expects('query');
        $query->andReturnUsing(
            static fn (string $sql): FakeStatement => str_starts_with($sql, 'SHOW CREATE TABLE')
                ? $showCreateStatement
                : $selectStatement,
        );
        $query->times(2);

        $service = new DatabaseBackupService($database);
        $tempName = tempnam(sys_get_temp_dir(), 'backup_test_');
        Assert::true($tempName !== false);
        $filePath = $tempName . '.sql.gz';

        try {
            $service->writeGzippedDump($filePath);
            $decoded = gzdecode((string) file_get_contents($filePath));
            Assert::true($decoded !== false);
            $sql = $decoded;

            Assert::true(str_contains($sql, 'SET FOREIGN_KEY_CHECKS=0;'));
            Assert::true(str_contains($sql, 'DROP TABLE IF EXISTS `widgets`;'));
            Assert::true(str_contains($sql, 'CREATE TABLE `widgets` (`id` int NOT NULL);'));
            Assert::true(str_contains($sql, "INSERT INTO `widgets` VALUES"));
            Assert::true(str_contains($sql, "(" . "'1','Widget A')"));
            Assert::true(str_contains($sql, "('2',NULL)"));
            Assert::true(str_contains($sql, 'SET FOREIGN_KEY_CHECKS=1;'));
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function writeGzippedDumpThrowsWhenFileCannotBeOpened(): void
    {
        /** @var DatabaseInterface&m\MockInterface $database */
        $database = m::mock(DatabaseInterface::class);

        $service = new DatabaseBackupService($database);

        $threw = false;
        try {
            // A path in a non-existent directory can never be opened for writing.
            $service->writeGzippedDump(sys_get_temp_dir() . '/no-such-dir-xyz/backup.sql.gz');
        } catch (\RuntimeException) {
            $threw = true;
        }
        Assert::true($threw);
    }
}
