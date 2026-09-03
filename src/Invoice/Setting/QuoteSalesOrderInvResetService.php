<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use Cycle\Database\DatabaseInterface;
use Cycle\Schema\Provider\SchemaProviderInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Drops the entire Inv, Quote and SalesOrder entity trees and clears the
 * cached Cycle ORM schema so the next request rebuilds every table fresh
 * from current entity definitions (Cycle\Schema\Generator\SyncTables, wired
 * unconditionally in config/common/params.php's 'schema-providers' pipeline
 * -- see feedback_cycle_orm_schema_sync memory).
 *
 * This automates, for debug/dev use only, exactly the manual recovery steps
 * taken during the 2026-09-03 sales_order_amount.so_id production incident
 * (see project_sales_order_amount_so_id_column_incident memory): a
 * mysqldump-equivalent backup, then DROP TABLE the full child/grandchild
 * tree of each root entity, then a fresh schema rebuild. Gating (debug
 * mode, permission, DB password) is entirely the caller's responsibility --
 * see InvoiceController::resetQuoteSalesOrderInv().
 */
final class QuoteSalesOrderInvResetService
{
    /**
     * Children/grandchildren before parents, to avoid FK integrity errors
     * even with FOREIGN_KEY_CHECKS left on. See InvoiceController's
     * InvTruncate1Command/QuoteTruncate2Command/SalesOrderTruncate3Command
     * sibling commands for the equivalent row-level (not schema-level)
     * operation this mirrors.
     */
    private const array INV_TABLES = [
        'inv_item_amount',
        'inv_item_allowance_charge',
        'inv_allowance_charge',
        'inv_custom',
        'inv_tax_rate',
        'inv_sent_log',
        'inv_recurring',
        'inv_item',
        'inv_amount',
        'inv',
    ];

    private const array QUOTE_TABLES = [
        'quote_item_amount',
        'quote_item_allowance_charge',
        'quote_allowance_charge',
        'quote_custom',
        'quote_tax_rate',
        'quote_item',
        'quote_amount',
        'quote',
    ];

    private const array SALES_ORDER_TABLES = [
        'sales_order_item_amount',
        'sales_order_item_allowance_charge',
        'sales_order_allowance_charge',
        'sales_order_custom',
        'sales_order_tax_rate',
        'sales_order_item',
        'sales_order_amount',
        'sales_order',
    ];

    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly SchemaProviderInterface $schemaProvider,
        private readonly DatabaseBackupService $backupService,
        private readonly Aliases $aliases,
    ) {
    }

    /**
     * @return list<string> every table name, in drop order.
     */
    public static function tables(): array
    {
        return [...self::INV_TABLES, ...self::QUOTE_TABLES, ...self::SALES_ORDER_TABLES];
    }

    /**
     * Backs up the database, drops every Inv/Quote/SalesOrder table, then
     * clears the cached schema so the *next* request recreates them fresh
     * -- this method alone does not recreate anything, since the request
     * that calls it has already resolved (and cached, for its own
     * lifetime) the current ORM schema by the time any controller action
     * runs. See InvoiceController::resetQuoteSalesOrderInv() /
     * resetQuoteSalesOrderInvFinish() for how the two legs are split
     * across requests, and resetAutoIncrement()'s docblock for why that
     * split matters.
     *
     * The backup runs first and its exceptions are left to propagate
     * uncaught -- if it fails, nothing is dropped.
     *
     * @return list<string> the tables that were dropped, in drop order.
     */
    public function dropAndClearSchema(): array
    {
        $this->backup();

        $tables = self::tables();

        $this->database->execute('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) {
                $this->database->execute("DROP TABLE IF EXISTS `{$table}`");
            }
        } finally {
            $this->database->execute('SET FOREIGN_KEY_CHECKS=1');
        }

        // Force the next request's schema pipeline to rebuild from current
        // entity definitions instead of reading the (now stale) cache --
        // see PhpFileSchemaProvider::read(): a missing cache file falls
        // through to a fresh compile + SyncTables regardless of the
        // BUILD_DATABASE env flag.
        $this->schemaProvider->clear();

        return $tables;
    }

    /**
     * Sets AUTO_INCREMENT back to 1 for every table in the given list --
     * mirrors AutoIncrementSetToOneAfterTruncate6Command's own SQL exactly,
     * scoped to just these tables rather than the whole database.
     *
     * Callers MUST only call this once the tables have actually been
     * recreated by a fresh schema rebuild (i.e. from a *separate* request
     * than the one that called dropAndClearSchema() -- any controller
     * action's own construction resolves the ORM schema, which is what
     * triggers Cycle\Schema\Generator\SyncTables to CREATE the tables
     * fresh, before that action's body runs). Calling this too early is
     * safe but a no-op per table -- DatabaseInterface::execute() on a
     * missing table simply throws, which is why this is a distinct method
     * rather than being folded into dropAndClearSchema().
     *
     * @param list<string> $tables
     */
    public function resetAutoIncrement(array $tables): void
    {
        foreach ($tables as $table) {
            $this->database->execute("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        }
    }

    private function backup(): void
    {
        $directory = $this->aliases->get('@root') . '/backups';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new DatabaseBackupException('Unable to create backup directory: ' . $directory);
        }
        $filePath = $directory . '/invoice_pre_reset_' . date('Ymd_His') . '.sql.gz';
        $this->backupService->writeGzippedDump($filePath);
    }
}
