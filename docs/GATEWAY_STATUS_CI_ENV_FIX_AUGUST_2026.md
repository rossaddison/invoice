# Gateway Status CI Fix — Missing `.env` in a Fresh Checkout (August 2026)

## The failure

The first real run of `.github/workflows/gateway-status.yml` (after
repo secrets were added) failed immediately:

```
Run php yii gateway-status/check-sandboxes
PHP Fatal error:  Uncaught Dotenv\Exception\InvalidPathException: Unable
to read any of the environment file(s) at
[/home/runner/work/invoice/invoice/.env]. in
/home/runner/work/invoice/invoice/vendor/vlucas/phpdotenv/src/Store/FileStore.php:68
```

## Root cause

`.env` is gitignored — it holds real secrets, so it's never in a fresh
checkout. `autoload.php`, which every `./yii` console invocation
requires, calls:

```php
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();   // not safeLoad() — throws if the file is missing
```

`load()` (as opposed to `safeLoad()`) throws a hard fatal when the file
doesn't exist at all, rather than skipping quietly. Every other CI
workflow in this repo happens to avoid this entirely:

- `Tests/bootstrap.php` requires `vendor/autoload.php` directly — it
  never touches the root `autoload.php` or its `Dotenv` call.
- `benchmark.yml` runs `php benchmarks/run.php`, a separate entrypoint
  that also never requires the root `autoload.php`.

`gateway-status.yml` was the first workflow to actually invoke
`php yii ...`, so it was the first to hit this.

## Fix

Added one step before the first `php yii` call:

```yaml
- name: Create .env for console bootstrap
  run: cp .env.example .env
```

`.env.example`'s blank/placeholder values (`DB_NAME=`, `DB_USERNAME=`,
etc.) are safe here — `gateway-status/check-sandboxes` and
`gateway-status/rebuild` only ever touch the `gateway_status` SQLite
connection, a deliberately separate Cycle ORM connection from the app's
main MySQL one (see `config/common/params.php` and
`docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md`) — the blank MySQL
credentials are never exercised by either command.

## Verification

Simulated the CI condition locally rather than guessing: backed up the
real local `.env`, copied `.env.example` over it, and ran both console
commands directly.

```
$ php yii gateway-status/check-sandboxes
Skipping Stripe: STRIPE_SANDBOX_SECRET_KEY not set.
Skipping Mollie: MOLLIE_SANDBOX_API_KEY not set.
Skipping Adyen: ADYEN_SANDBOX_API_KEY not set.
Skipping GoCardless: GOCARDLESS_SANDBOX_ACCESS_TOKEN not set.
Skipping Square: SQUARE_SANDBOX_ACCESS_TOKEN not set.
Checked 0 gateway(s) with a configured secret.
[OK] Sandbox checks complete.

$ php yii gateway-status/rebuild
Synced 14 gateway row(s).
[OK] Gateway status rebuilt.
```

Both ran cleanly with no MySQL connection attempted. Real `.env`
restored afterward; `git status` confirmed it stayed untracked and no
other files were touched by the test run.
