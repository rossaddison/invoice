<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Invoice\Setting\DatabaseBackupException;
use App\Invoice\Setting\DatabaseBackupService;
use App\Invoice\Setting\QuoteSalesOrderInvResetService;
use Cycle\Database\DatabaseInterface;
use Cycle\Schema\Provider\SchemaProviderInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * QuoteSalesOrderInvResetService drops and rebuilds the exact table trees
 * behind the 2026-09-03 sales_order_amount.so_id production incident (see
 * project_sales_order_amount_so_id_column_incident memory) -- these tests
 * lock in the exact table list and drop/backup/rebuild ordering, since a
 * silent regression here (a missing table, a reordered drop, a skipped
 * backup) is exactly the class of bug that caused that incident in the
 * first place.
 *
 * dropAndClearSchema() only drops tables and clears the cached schema --
 * it does NOT recreate them (that happens as a side effect of the *next*
 * request's own controller construction resolving the ORM schema fresh;
 * see InvoiceController::resetQuoteSalesOrderInv()'s docblock), so there is
 * nothing here asserting CREATE TABLE ever runs -- that part is out of
 * this file's scope, being Cycle's own SyncTables generator, not this
 * service's code.
 */
#[Test]
final class QuoteSalesOrderInvResetServiceTest
{
    /**
     * @return array{0: DatabaseInterface&m\MockInterface, 1: SchemaProviderInterface&m\MockInterface,
     *     2: DatabaseBackupService&m\MockInterface, 3: Aliases&m\MockInterface}
     */
    private function makeDeps(): array
    {
        /** @var DatabaseInterface&m\MockInterface $database */
        $database = m::mock(DatabaseInterface::class);
        /** @var SchemaProviderInterface&m\MockInterface $schemaProvider */
        $schemaProvider = m::mock(SchemaProviderInterface::class);
        /** @var DatabaseBackupService&m\MockInterface $backupService */
        $backupService = m::mock(DatabaseBackupService::class);
        /** @var Aliases&m\MockInterface $aliases */
        $aliases = m::mock(Aliases::class);
        $aliases->shouldReceive('get')->with('@root')->andReturn(
            'C:\Users\rossa\AppData\Local\Temp\claude\c--wamp64-www-invoice'
            . '\df662089-7612-481e-9495-7371ecd861a9\scratchpad\reset-service-test',
        );
        return [$database, $schemaProvider, $backupService, $aliases];
    }

    public function tablesReturnsTheFullInvQuoteSalesOrderTreeInDropOrder(): void
    {
        Assert::same(QuoteSalesOrderInvResetService::tables(), [
            'inv_item_amount', 'inv_item_allowance_charge', 'inv_allowance_charge',
            'inv_custom', 'inv_tax_rate', 'inv_sent_log', 'inv_recurring',
            'inv_item', 'inv_amount', 'inv',
            'quote_item_amount', 'quote_item_allowance_charge', 'quote_allowance_charge',
            'quote_custom', 'quote_tax_rate', 'quote_item', 'quote_amount', 'quote',
            'sales_order_item_amount', 'sales_order_item_allowance_charge',
            'sales_order_allowance_charge', 'sales_order_custom', 'sales_order_tax_rate',
            'sales_order_item', 'sales_order_amount', 'sales_order',
        ]);
    }

    public function dropAndClearSchemaBacksUpThenDropsEveryTableWithForeignKeyChecksToggledThenClearsTheSchemaCache(): void
    {
        [$database, $schemaProvider, $backupService, $aliases] = $this->makeDeps();

        $backupService->shouldReceive('writeGzippedDump')->once()
            ->with(m::pattern('/invoice_pre_reset_\d{8}_\d{6}\.sql\.gz$/'))
            ->ordered();

        $database->shouldReceive('execute')->once()->with('SET FOREIGN_KEY_CHECKS=0')->ordered();
        foreach (QuoteSalesOrderInvResetService::tables() as $table) {
            $database->shouldReceive('execute')->once()
                ->with("DROP TABLE IF EXISTS `{$table}`")->ordered();
        }
        $database->shouldReceive('execute')->once()->with('SET FOREIGN_KEY_CHECKS=1')->ordered();

        $schemaProvider->shouldReceive('clear')->once()->ordered();

        $service = new QuoteSalesOrderInvResetService($database, $schemaProvider, $backupService, $aliases);
        $result = $service->dropAndClearSchema();

        Assert::same($result, QuoteSalesOrderInvResetService::tables());
    }

    #[ExpectException(DatabaseBackupException::class)]
    public function dropAndClearSchemaNeverDropsAnythingWhenTheBackupFails(): void
    {
        [$database, $schemaProvider, $backupService, $aliases] = $this->makeDeps();

        $backupService->shouldReceive('writeGzippedDump')->once()
            ->andThrow(new DatabaseBackupException('disk full'));
        $database->shouldNotReceive('execute');
        $schemaProvider->shouldNotReceive('clear');

        $service = new QuoteSalesOrderInvResetService($database, $schemaProvider, $backupService, $aliases);
        $service->dropAndClearSchema();
    }

    #[ExpectException(\RuntimeException::class)]
    public function dropAndClearSchemaStillReEnablesForeignKeyChecksWhenADropFails(): void
    {
        [$database, $schemaProvider, $backupService, $aliases] = $this->makeDeps();

        $backupService->shouldReceive('writeGzippedDump')->once();
        $database->shouldReceive('execute')->once()->with('SET FOREIGN_KEY_CHECKS=0');
        $database->shouldReceive('execute')->once()
            ->with('DROP TABLE IF EXISTS `inv_item_amount`')
            ->andThrow(new \RuntimeException('table is locked'));
        // The finally block must still re-enable FK checks even though a
        // drop mid-loop threw.
        $database->shouldReceive('execute')->once()->with('SET FOREIGN_KEY_CHECKS=1');
        $schemaProvider->shouldNotReceive('clear');

        $service = new QuoteSalesOrderInvResetService($database, $schemaProvider, $backupService, $aliases);
        $service->dropAndClearSchema();
    }

    public function resetAutoIncrementRunsAlterTableForEveryGivenTable(): void
    {
        [$database, $schemaProvider, $backupService, $aliases] = $this->makeDeps();

        $database->shouldReceive('execute')->once()->with('ALTER TABLE `quote` AUTO_INCREMENT = 1');
        $database->shouldReceive('execute')->once()->with('ALTER TABLE `sales_order` AUTO_INCREMENT = 1');

        $service = new QuoteSalesOrderInvResetService($database, $schemaProvider, $backupService, $aliases);
        $service->resetAutoIncrement(['quote', 'sales_order']);
    }
}
