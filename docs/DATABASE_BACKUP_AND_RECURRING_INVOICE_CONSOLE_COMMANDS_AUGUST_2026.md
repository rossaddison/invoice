# Database Backup Feature + Recurring Invoice / Backup Console Commands (August 2026)

## Background

A local-disk MySQL backup was originally done by hand: `mysqldump` piped over
SSH from the live Alpine server, then `scp`'d down to a local machine. That
workflow still works for an ad-hoc copy, but has no PHP equivalent and no way
to run unattended on a schedule.

## Settings → Backup tab (on-demand download)

`DatabaseBackupService` (`src/Invoice/Setting/DatabaseBackupService.php`)
writes a gzip-compressed SQL dump (schema + data, every table) entirely
through Cycle ORM's own DBAL — no `mysqldump` binary, no `shell_exec()` — so
the feature works unmodified on shared hosting where `exec()`-family
functions are commonly disabled. Rows are streamed and written in batches
rather than buffered in memory.

A new **Settings → Backup** tab (`SettingController::downloadBackup()`,
`partial_settings_backup.php`) lets an admin trigger a dump on demand and
download it straight from the browser, generating to a temp file and
streaming it as the response.

**Real bug hit in production**: `Cycle\Database\Driver\DriverInterface::quote(mixed $value, ...)`
declares a loose `mixed` parameter, but the concrete implementation calls
native `PDO::quote()`, whose actual parameter type is a strict `string` —
passing an `int` column value threw `PDO::quote(): Argument #1 ($string)
must be of type string, int given` on `yii3i.online`. Fixed by casting
`(string) $value` before calling `->quote()`. Reported twice by the user
because the first fix had only been pushed to GitHub, not yet pulled onto
the live server — resolved once `git pull` ran on the remote box. Verified
end-to-end: user re-tested live and the backup downloaded successfully.

`Tests/Testo/Invoice/Setting/DatabaseBackupServiceTest.php` covers the
service against a hand-written `FakeStatement` (Mockery can't mock
`Cycle\Database\StatementInterface` directly since it extends
`\Traversable`, which PHP requires a concrete class to satisfy via
`Iterator`/`IteratorAggregate`) — schema + data emitted per table, `NULL`
handled distinctly from quoted values, foreign-key-check toggling wraps the
whole dump.

## Console commands (unattended / cron-friendly)

Two new commands, following the existing `system/check-php-version`
(`CheckPhpVersionCommand`) shape:

- **`php yii setting/backup-database [--keep=N]`**
  (`src/Invoice/Setting/Console/BackupDatabaseCommand.php`) — writes a
  timestamped dump (`invoice_backup_YmdHis.sql.gz`) to a persistent
  `@root/backups` directory (rather than a throwaway temp file) and prunes
  down to the most recent `N` (default 14). Live-verified against the local
  MySQL database.

- **`php yii invrecurring/process`**
  (`src/Invoice/InvRecurring/Console/ProcessRecurringInvoicesCommand.php`) —
  creates due recurring invoices and sends Telegram balance reminders,
  replacing the pre-existing `curl` + `cron_key`-in-URL HTTP trigger
  (`InvRecurringController::cron()`) with a real console entry point
  suitable for a server crontab. The HTTP endpoint is kept working (see
  below) rather than removed, since removing a live production route needs
  separate explicit confirmation.

Both are registered in `config/console/params.php` and confirmed resolvable
via `php yii list`.

## InvRecurringCronService extraction + a real pre-existing bug fix

`InvRecurringController::cron()`'s invoice-generation logic (and its private
helpers — resolving the recurring user, creating the invoice from the
client's product catalog, advancing the recurring date, sending the
Telegram reminder) was extracted into a new
`src/Invoice/InvRecurring/InvRecurringCronService.php`, shared by both the
legacy HTTP endpoint (now a thin wrapper) and the new console command.
`addProductItemsToInv()` is also reused by
`InvRecurringController::createFromProductClient()`, which builds the first
invoice of a new recurring schedule the same way the cron builds each
subsequent one.

Extracting this logic surfaced a real, live bug in
`InvRecurringRepository::active()`/`CountActive()`: both queried
non-existent `next_date`/`end_date` columns (confirmed via `DESCRIBE
inv_recurring` against the real local database — the actual columns are
`next`/`end`), combined with broken OR-only logic instead of proper
"due AND not-ended" semantics. Fixed with a shared `dueQuery()` helper:

```php
private function dueQuery(): Select
{
    $today = date('Y-m-d');
    return $this->select()
        ->where('next', '<=', $today)
        ->where(static function (QueryBuilder $q) use ($today): void {
            $q->where('end', null)->orWhere('end', '>=', $today);
        });
}
```

`CountActive()` turned out to have zero callers anywhere in the app (dead
code, left as-is); `active()`'s only caller is the cron logic itself.

Six new Testo tests
(`Tests/Testo/Invoice/InvRecurring/InvRecurringCronServiceTest.php`) cover
`resolveAdminUser()` (found / not-found), `process()` (skip when base
invoice missing, advance-date-but-skip-invoice when not consented, full
create-from-products happy path), and `addProductItemsToInv()`'s
skip-on-null/unresolved-product branches. `Yiisoft\Data\Cycle\Reader\EntityReader`
is `final`; rather than hand-writing a stub, it's mocked directly via
Mockery (enabled for final classes by this project's existing
`DG\BypassFinals::enable()` in `testo.php`) with `getIterator()` stubbed to
yield fixed items.

Extracting the logic also left `InvRecurringController`'s constructor
carrying two now-unused dependencies (`Logger`, `InvItemService`) and a few
now-dead imports (`TelegramHelper`, `IiAddProductDeps`,
`Infrastructure\Persistence\InvItem\InvItem`) — removed.

## Verification

Full-project `vendor/bin/psalm --no-cache`: **no errors found**, 100% type
inference. Full `vendor/bin/testo run`: 606 tests, 603 passed (the 3
failures are the pre-existing, unrelated `AmazonPayPaymentServiceTest`
RSA-key-generation environment errors). Full `vendor/bin/phpunit`: 3,877
tests, 10,354 assertions, all passing with the known-acceptable 23
pre-existing notices. `setting/backup-database` run live against the local
MySQL database and confirmed the `.sql.gz` output with `gzip -t`.
`invrecurring/process` was deliberately **not** run live in this session —
unlike the backup command, it would create real invoice rows and advance
schedule dates, a mutating and not-easily-reversible action best triggered
deliberately by a maintainer against a database they're watching.
