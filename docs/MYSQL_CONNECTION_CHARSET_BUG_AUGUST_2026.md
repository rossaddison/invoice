# MySQL Connection Charset Bug (August 2026)

## The incident

Immediately after deploying the
[PayPal webhook `decode()` fix](PAYPAL_WEBHOOK_ID_DECODE_BUG_AUGUST_2026.md),
production's PayPal webhook stopped 500ing at signature-verification time —
but a real PayPal sandbox payment (`INV119`) still didn't get marked paid.
Grepping production's own log for the new failure showed a completely
different exception, one line further into the same request:

```
PDOException: SQLSTATE[22007]: Invalid datetime format: 1366 Incorrect
string value: '\xF0\x9F\xAA\x9D T...' for column
`yii3_i`.`payment`.`note` at row 1
```

`\xF0\x9F\xAA\x9D` is the exact UTF-8 byte sequence for U+1FA9D 🪝 (HOOK) —
`PaymentRecordChannel::Webhook->emoji()`, prefixed onto every webhook-driven
`Payment.note` by `OnlinePaymentRecorderService::recordSuccess()` so an
admin can tell at a glance which mechanism confirmed a payment. This is a
genuinely 4-byte UTF-8 character, and MySQL was rejecting it as an invalid
value for that column.

Because `recorder->record()` is called *before* `PaypalWebhookHandler`
marks the invoice paid (`setStatusId(4)` + zeroing the balance), the
`PDOException` aborted the whole `markInvoicePaidIfVerified()` method right
there — so even though PayPal's own capture had genuinely gone through and
the webhook signature verified correctly, the invoice was left unpaid
because the very next step (writing the audit `Payment` row) threw first.

**Not PayPal-specific**: `PaymentRecordContext`'s `channel` defaults to
`PaymentRecordChannel::Webhook` for every gateway's webhook handler (only
two legacy call sites in `PaymentInformationController.php` override it to
`Redirect`). Any gateway's webhook-driven payment would have hit the
identical crash the first time it ran — PayPal just happened to be the one
under live test when it first fired.

## Root cause

The obvious first suspect — the `payment.note` column itself declared with
a narrow charset — turned out to be wrong. A direct
`information_schema.COLUMNS` query against production showed **every**
column in the database, including `payment.note`, already declared
`utf8mb4`/`utf8mb4_general_ci`, and the database's own
`information_schema.SCHEMATA` default is `utf8mb4`/`utf8mb4_general_ci`
too — confirmed via `SHOW CREATE DATABASE`-equivalent output. The schema
was never the problem.

The actual cause is one level up: **the runtime PDO connection itself**.
Neither of this app's two MySQL DSN strings —
`config/common/di/db.php` (the plain `yiisoft/db` connection) and
`config/common/params.php`'s Cycle ORM `'mysql'` connection (the one that
actually threw, since `Payment` is a Cycle-managed entity) — ever specified
`charset=utf8mb4` in the DSN. Without it, PDO/mysqlnd negotiates whatever
its own compiled-in default happens to be, independent of both the
database's default charset and every column's own declared charset. MySQL
then converts incoming bytes to *that* negotiated connection charset before
they ever reach the column — and if the negotiated charset can't represent
a 4-byte character, the conversion fails with exactly this 1366 error,
regardless of the column being fully utf8mb4-capable underneath.

This app's own one-time database-creation step
(`InstallCommand::createDatabase()`) already does this correctly —
`CREATE DATABASE ... CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci` —
which is exactly why the schema itself was clean. It's only the two
long-lived runtime connections used for every ordinary request that were
missing the equivalent explicit charset.

## Fix

Added `;charset=utf8mb4` to both DSN strings:

```php
// config/common/di/db.php
$dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';
```

```php
// config/common/params.php, yiisoft/yii-cycle 'mysql' connection
'mysql:host=' . $dbHost . ';dbname='. $dbName . ';charset=utf8mb4',
```

Purely additive — no schema or data migration involved, since the schema
was already correct. Both changes just make explicit what should already
have been true of the connection.

## Verification

Reproduced and confirmed the fix locally with a standalone script
connecting to the local WAMP MariaDB instance with and without the DSN
charset parameter, inserting the exact `🪝` byte sequence into a temporary
`utf8mb4` column and reading it back. This local server's own default
(`character_set_client` already negotiates `utf8mb4` even without the DSN
parameter — a modern MariaDB default) meant the failure mode itself
couldn't be reproduced locally, but the fixed connection round-tripped the
real emoji correctly end-to-end (`🪝 test webhook` read back byte-for-byte),
confirming the fix is correct regardless of what a given server's default
happens to be — which is exactly the ambiguity an explicit charset
eliminates for good.

`php -l` clean on both changed files. Full-project `vendor/bin/psalm
--no-cache`: no errors. Full Testo suite: 805/805 passing (no regressions;
this fix has no dedicated Testo coverage of its own, since the Testo suite
runs against mocked repositories, not a real MySQL connection).

Requires a production deploy (`git pull` + Apache restart) before a real
PayPal webhook can be retested end-to-end against `INV119`/a fresh
sandbox payment.
