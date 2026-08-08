# Gateway Status CI Fixes — First Real Run, Three Bugs Deep (August 2026)

Three separate, sequential failures on the *very first* real run of
`.github/workflows/gateway-status.yml` (after repo secrets were added),
each only surfacing once the previous one was fixed: a missing `.env`,
an eager Cycle ORM schema build needing a live MySQL server, and a
read-only default `GITHUB_TOKEN`. Filename kept as originally created
(the `.env` fix); the other two follow-up bugs are documented in the
same file below since they're one continuous debugging session, not
separate efforts.

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
etc.) looked safe for the same reason `gateway-status/check-sandboxes`
and `gateway-status/rebuild` only ever *use* the `gateway_status`
SQLite connection, never the app's main MySQL one (see
`config/common/params.php` and `docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md`).

**This turned out to be wrong** — see "Follow-up bug #1" below. The
local verification below was real and passed, but it passed by
accident: local `.env` (before it was overwritten with the
`.env.example`-derived copy) pointed at a real, reachable local MySQL
server, so the schema-build step this doc didn't yet know about
succeeded silently instead of failing loudly like it did in CI, where
no MySQL server exists at all.

## Verification (partial — see follow-up below)

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

Real `.env` restored afterward; `git status` confirmed it stayed
untracked and no other files were touched by the test run. This
confirmed the `.env`-missing fatal was fixed, but — because a real
MySQL server was reachable throughout, just via the restored `.env`'s
real credentials rather than the blank `.env.example` ones — it didn't
catch the next failure, which only a genuinely MySQL-less environment
(i.e. actual CI) could surface.

## Follow-up bug #1 — eager Cycle ORM schema build needs a live MySQL server

The very next real CI run (secrets now in place) got past the `.env`
fatal and hit a new one:

```
PHP Fatal error:  Uncaught PDOException: SQLSTATE[HY000] [2002] No such
file or directory in .../vendor/cycle/database/src/Driver/Driver.php:663
...
Next Yiisoft\Di\BuildingException: Caught unhandled error "SQLSTATE[HY000]
[2002] No such file or directory" while building "Cycle\ORM\SchemaInterface".
```

**Root cause**: every `php yii ...` call builds the full app DI
container, which eagerly compiles the Cycle ORM schema for *every*
registered database connection — the `mysql` "default" one *and* the
separate `gateway_status` SQLite one — not just whichever one the
command in question actually uses. This is cheap and invisible when a
cached `runtime/schema.php` already exists (the normal case on any
machine that's run the app before), because then nothing needs
introspecting live. But `runtime/` is entirely gitignored
(`runtime/.gitignore` → `*`), so a fresh CI checkout never has that
cache, and schema compilation falls through to live introspection via
`FromConveyorSchemaProvider` — which needs a *reachable MySQL server*,
regardless of whether the command that triggered container bootstrap
ever touches MySQL itself. `gateway-status/check-sandboxes` and
`gateway-status/rebuild` don't — the failure happens during container
construction, before either command runs a single line of its own
logic.

**Fix**: gave the job a real (empty) MySQL service container —
matching what a first-ever local install already does per
`config/common/params.php`'s own `schema-providers` comment, not a
workaround invented for this workflow:

```yaml
services:
  mysql:
    image: mysql:8.4
    env:
      MYSQL_ALLOW_EMPTY_PASSWORD: yes
      MYSQL_DATABASE: yii3_i
    ports:
      - 3306:3306
    options: >-
      --health-cmd="mysqladmin ping"
      --health-interval=10s
      --health-timeout=5s
      --health-retries=10
```

Two supporting changes were needed alongside it:

- `pdo_mysql` added explicitly to the `Install PHP` step's extension
  list (previously present only implicitly/by setup-php default).
- `DB_HOST_IP_ADDRESS` overridden to `127.0.0.1` in the generated
  `.env` (`sed -i 's/^DB_HOST_IP_ADDRESS=.*/DB_HOST_IP_ADDRESS=127.0.0.1/' .env`)
  — PDO's MySQL driver treats the default `localhost` as "connect via
  Unix socket," which doesn't exist for a service container; only an
  explicit IP forces a real TCP connection to the service above.

Could not be dry-run locally the way the `.env` fix was — no
disposable MySQL instance was available without starting Docker
Desktop (installed but not running here) — so this one needed a real
CI run to confirm. It worked: `Check gateway sandboxes` and
`Rebuild gateway status` both completed successfully on the next run.

## Follow-up bug #2 — default `GITHUB_TOKEN` is read-only

With both prior fixes in place, every step succeeded except the last
one, `Commit results`, with exit code 128:

```
[main d3babde] chore: weekly gateway status check 2026-08-08
 2 files changed, 10 insertions(+), 10 deletions(-)
remote: Permission to rossaddison/invoice.git denied to github-actions[bot].
fatal: unable to access 'https://github.com/rossaddison/invoice/': The
requested URL returned error: 403
```

The `git commit` itself succeeded — the working tree in the runner had
the change staged and committed fine. Only the `git push` was denied.
Confirmed via `gh api repos/rossaddison/invoice/actions/permissions/workflow`
that this repo's **default workflow permission is `read`** — a
repo-level setting (Settings → Actions → General → Workflow
permissions), not something specific to how this workflow was
authored. `benchmark.yml`'s near-identical commit-back step likely has
the same latent gap; not touched here, worth checking next time it
actually has a diff to push.

**Fix**: opted this one workflow into write access explicitly, rather
than changing the repo-wide default:

```yaml
permissions:
  contents: write
```

## Final verified result

The next run after all three fixes landed (`95a3a765` → `1a2dce0b` →
`bf881d0e`) completed clean end to end: `check-sandboxes` pinged all
five configured gateways' real sandbox APIs and got `pass` for every
one (Stripe, Mollie, Adyen, GoCardless, Square), `rebuild` synced the
results into `gateway-status.sqlite`, and `Commit results` pushed
`be08b20c "chore: weekly gateway status check 2026-08-08"` straight to
`main` — the workflow's `github-actions[bot]` commit, not a manual one.
The weekly Monday 03:00 UTC cron now runs this same, now-proven-working
path unattended.
