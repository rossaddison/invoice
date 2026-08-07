# Including Codeception's Functional Suite in Coverage — August 2026

## Summary

SonarCloud's PHP coverage percentage only ever reflected `Tests/Unit` and
`Tests/PHPUnit` (via `vendor/bin/phpunit --coverage-clover`) plus
`Tests/Testo` (via `vendor/bin/testo --coverage-clover`). Real HTTP-level
code paths exercised by the Functional suite's Cest tests — actual
routing, middleware, controller actions, RBAC checks — were never
instrumented at all, so lines only reachable through a real request
(rather than a unit test with mocked dependencies) counted as uncovered
even though genuine tests do exercise them.

## Why only the Functional suite, not Acceptance/Cli too

- **Functional** spawns its own PHP built-in server via the
  `Codeception\Extension\RunProcess` extension (`Tests/Functional.suite.yml`)
  on the same machine as the `codecept` process — in both CI (one Ubuntu
  runner) and locally (one WAMP box), coverage collector and instrumented
  app share a filesystem. That's exactly the scenario `codeception/c3`'s
  **local** merge mode (as opposed to genuinely remote) is built for.
- **Acceptance** targets `http://invoice.myhost`, a real Apache vhost that
  only exists on local WAMP — not available at all in the `sonar` CI job
  (`ubuntu-latest`, no vhost setup), so remote coverage collection isn't
  feasible there without separate infrastructure work.
- **Cli** shells out to `php yii ...` as its own subprocesses through
  Codeception's `Cli` module, not HTTP — C3's header/cookie-triggered
  mechanism doesn't apply; a console-side coverage dump would need a
  different approach entirely.

Both are worth revisiting later; this pass covers Functional only.

## The three pre-existing bugs this uncovered

This app already had `codeception/c3` in `require-dev` and a `YII_C3`
env-var gate in `public/index.php` — evidently wired in at some point but
never actually exercised, since nobody had set `YII_C3=1` before. All
three bugs below were **dormant**, not introduced today.

**1. Wrong require path.** `public/index.php` required
`vendor/codeception/c3/c3.php` directly. `codeception/c3` is a Composer
plugin (`Codeception\c3\Installer`) that copies its own `c3.php` to the
project root on every `composer install`/`update` — a real, gitignored
`c3.php` already existed there. `c3.php`'s own code resolves several
paths via `realpath(__DIR__)` (its fallback autoloader, and — separately
— where to find `codeception.yml`), which only resolve correctly when
`__DIR__` is the project root, matching where the Composer plugin puts
it. Requiring the vendor copy instead meant every `__DIR__`-relative
lookup pointed at `vendor/codeception/c3/`, which doesn't contain a
`vendor/autoload.php` or a `codeception.yml` of its own. Fixed by
pointing at `dirname(__DIR__) . '/c3.php'` instead.

**2. Wrong require order.** Even pointed at the right file, `c3.php` was
required *before* this app's own Composer autoloader
(`dirname(__DIR__) . '/autoload.php'`), so `\Codeception\Codecept`
genuinely didn't exist yet when `c3.php` checked for it — triggering its
own (broken, see bug 1) fallback autoload attempt. Fixed by moving the
`YII_C3` block to after `autoload.php` is required.

**3. Undefined-constant crash in c3.php's own error handler.**
`__c3_error()` falls back to `C3_CODECOVERAGE_MEDIATE_STORAGE` when
`C3_CODECOVERAGE_ERROR_LOG_FILE` isn't defined — but that fallback
constant is itself only defined *after* c3.php's own config-loading step
succeeds, so any error *before* that point (like bugs 1 and 2, before
they were fixed) crashed with a misleading "Undefined constant" fatal
instead of a readable message. Defining `C3_CODECOVERAGE_ERROR_LOG_FILE`
up front (an option c3's own README documents) fixed this regardless of
what else goes wrong — errors now land in `runtime/logs/c3_error.log`
instead of crashing the request.

A fourth issue surfaced but isn't fatal: `phpunit/php-code-coverage` v14
(this project's version) removed `Report\PHP` (the native-PHP-serialization
report writer), which `c3.php`'s own backward-compatibility shim still
references via `class_alias()`. That only raises a harmless `E_WARNING`
— the shim silently no-ops for that one format — and doesn't affect the
Clover report this setup actually uses.

## Configuration added

- **`Tests/Functional.suite.yml`**: a `coverage: { enabled: true,
  include: [src/*, config/*] }` block (local mode — `remote` defaults to
  `false`, correct here), and `-d xdebug.mode=coverage` added to the
  spawned server's command line (needed locally, where Xdebug is the
  driver; a no-op in CI, which uses PCOV and doesn't need mode-switching).
- **`.github/workflows/invoice_build.yml`**: a new `Generate Functional
  suite coverage (via C3 remote collection)` step in the `sonar` job,
  `YII_C3: '1'` set only for that step's `env:`, running
  `vendor/bin/codecept run Functional --coverage-xml=coverage-functional.xml`.
- **`sonar-project.properties`**: `coverage-functional.xml` added to
  `sonar.php.coverage.reportPaths`.
- **`public/index.php`**: the three fixes above.

## Verification

Isolated the failure from the full test harness by hitting the spawned
server's `/c3/report/clear` endpoint directly with `curl` and the
`X-Codeception-CodeCoverage` header, checking `runtime/logs/c3_error.log`
and for `Tests/_output/c3tmp/` after each fix — this pinned down each of
the three bugs individually before re-running the full suite. See the
`sonar` job's next run for the actual coverage percentage change.
