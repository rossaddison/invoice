# Fixing `codecept run` Failures on `final` Classes — August 2026

## Summary

`vendor/bin/codecept run` (all 4 suites, or `codecept run Unit` alone) was
failing 97 tests with `PHPUnit\Framework\MockObject\Generator\ClassIsFinalException`
on any test that mocks one of this app's `final` repository/service
classes (e.g. `QuoteItemRepository`). The exact same tests pass 100% clean
via `vendor/bin/phpunit --testsuite=Unit` and `vendor/bin/testo
--suite=Unit`, which is why this went unnoticed — those are this
project's normal day-to-day test commands; `codecept run` (invoking all
suites together) is not used routinely.

## Root cause

`phpunit.xml.dist` wires `bootstrap="Tests/bootstrap.php"`, which calls
`DG\BypassFinals::enable()` — the `dg/bypass-finals` package already in
`require-dev`, which strips `final`/`readonly` from source code on the fly
via a stream wrapper, specifically so PHPUnit's mock generator can double
classes that are otherwise undoubleable. Codeception's suite YAML files
(`Tests/Unit.suite.yml` etc.) never referenced this bootstrap file at all
— so when Codeception runs `*Test.php` files itself, `BypassFinals` never
activates, and `final` stays fully enforced.

## Fix, in two parts

**1. Wire the bootstrap in globally**, not per-suite. `codeception.yml`
now has a top-level `bootstrap: bootstrap.php` (resolves relative to
`paths.tests`, i.e. `Tests/bootstrap.php`). This has to be global, not set
only on `Tests/Unit.suite.yml`: `codecept run` (no suite argument) runs
Acceptance, Cli, and Functional before Unit, in one continuous PHP
process, and classes like `Yiisoft\Router\CurrentRoute` get autoloaded
normally (genuinely `final`, irreversibly, since PHP can't "un-declare" a
class) before Unit's own bootstrap would otherwise have run. A
suite-scoped bootstrap was too late — global bootstrap runs before any
suite starts.

**2. Scope `BypassFinals` away from PHPUnit's own package.** Enabling it
globally uncovered a second problem: by default `BypassFinals::enable()`
rewrites *everything*, including `vendor/phpunit/phpunit` itself. That
corrupts PHPUnit's own class hierarchy — `TestStatus\Error` stopped
matching its own `readonly` parent `TestStatus\Known` once bypass-finals
stripped `readonly` from one file but the other had already loaded
differently, crashing with `Non-readonly class ... Error cannot extend
readonly class ... Known` — a fatal error that aborted the whole test run
partway through, not just a handful of test failures.

`Tests/bootstrap.php` now calls `DG\BypassFinals::denyPaths([...])` before
`enable()`, explicitly excluding `vendor/phpunit/*`, `vendor/sebastian/*`,
`vendor/myclabs/*`, `vendor/theseer/*`, and `vendor/phar-io/*` — PHPUnit's
own package plus the core internals it's built directly on. Everything
else stays bypassable: this app's own `final` classes under `src/`, and
third-party framework classes tests do mock (`Yiisoft\Router\CurrentRoute`
being the one that surfaced this).

## Verification

- `vendor/bin/codecept run` (all 4 suites, one process): `OK (3892 tests,
  10395 assertions)` — was 97 errors, then a fatal crash after the first
  fix, now clean.
- `vendor/bin/codecept run Unit` alone: `OK (3824 tests, 10243
  assertions)`.
- `vendor/bin/phpunit --testsuite=Unit`: still `OK (3824 tests, 10243
  assertions)` — confirms the bootstrap change didn't regress the
  commands this project actually runs day to day.
- `vendor/bin/testo --suite=Unit`: still `772 passed`.
- Full-project Psalm on `Tests/bootstrap.php`: no errors.
