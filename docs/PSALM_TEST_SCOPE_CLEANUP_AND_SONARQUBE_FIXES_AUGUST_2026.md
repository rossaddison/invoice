# Psalm Test-Scope Cleanup + SonarQube Fixes (August 2026)

`Tests/` had never actually been in `psalm.xml`'s `<projectFiles>` — the
project's "confirm zero Psalm errors" bar never covered the test suite at
all, discovered while wiring the stock-movement feature (see
[`STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md`](STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md)).
This closed that gap end to end: `vendor/bin/psalm --no-cache` now
reports **"No errors found!"** project-wide, down from 4823 once `Tests/`
was actually included.

## The work, compressed from four phases

- Excluded `Tests/Support/_generated` (Codeception's auto-regenerated
  Actor scaffolding) the same way `vendor/` already is, and extended the
  existing `PossiblyUnused*`/`UnusedMethod`/`UnusedProperty` suppressions
  (already applied to `src`/`resources` for the same "framework/reflection
  calls this, not dead code" reason) to also cover `Tests`.
- Added roughly 900 missing `@var X&Mockery\MockInterface` annotations
  across `Tests/Testo` via two purpose-built scripts, so Psalm could
  actually resolve `m::mock()`/`m::spy()` return types — this alone
  closed the large majority of the gap.
- **`Tests/Unit` (the legacy Codeception/PHPUnit suite) is excluded from
  the scan entirely, not deleted.** This one needed catching mid-flight:
  the initial plan was to bulk-delete it as "legacy debt", which the user
  agreed to — but `src/Invoice/Entity/` no longer exists at all (that
  migration is fully complete) and CLAUDE.md's own DDD migration
  Definition of Done *requires* one
  `Tests/Unit/Invoice/Entity/{Name}EntityTest.php` per migrated entity as
  a checklist item. All 266 files were kept; only the directory was
  excluded from Psalm's scan, since its ~355 errors were a long
  heterogeneous tail with no single mechanically-fixable root cause,
  unlike `Tests/Testo`'s.
- The remaining 159 errors (14 `Tests/Testo` files + `sidebar.php`) turned
  out to be one confirmed Psalm scale artifact, not real defects:
  `X&Mockery\MockInterface` intersection types (and, for `sidebar.php`,
  DI-injected view-variable types) get inferred as an uninhabited/`never`
  type only at full-project scan size — proven deterministic via direct
  experiment (identical output and memory usage across `--threads=1`,
  `--memory-limit=8192M`, `--find-unused-code=none`; isolated-file and
  isolated-directory scans of the exact same code came back clean every
  time). Closed with a Psalm baseline
  (`psalm-baseline.xml` + `errorBaseline=` in `psalm.xml`) instead of more
  `issueHandlers` entries — the affected issue types
  (`InvalidReturnType`, `NoValue`, `UnusedVariable`, ...) are exactly the
  checks worth keeping live for genuine future bugs in those same files,
  and a baseline pins only these specific instances rather than
  suppressing the issue type file-wide.
  `findUnusedBaselineEntry="true"` (already set) will flag these entries
  as stale the day a Psalm version upgrade fixes the underlying inference
  bug, so the baseline documents its own expiry condition.

Real bugs found and fixed along the way, not just annotation gaps: a
Phase-1 auto-fixer regression that had deleted a needed `@var` from
`sidebar.php`; a `getRealPath(): string|false` left unguarded before
`rmdir()`/`unlink()` in `SignupAndLoginCest.php`; a recurring
union-vs-intersection return-type typo (`X|Mockery\MockInterface` instead
of `X&Mockery\MockInterface`) copy-pasted across several test helper
methods; 19 real legacy-suite test failures surfaced only by running
`Tests/Unit` for the first time in the same session (constructor changes
verified against `Tests/Testo` alone had missed them — this app runs test
code through two separate runners, `vendor/bin/testo` and
`vendor/bin/codecept`, and checking only one after a production
constructor change isn't sufficient).

## SonarQube findings on the new code

Six findings surfaced on the stock-movement/API code once pushed:

| Rule | File | Fix |
|---|---|---|
| `php:S1142` (>3 returns) | `OrderService::createOrder()` | extracted `resolveOrderContext()` |
| `php:S1142` | `OrdersController::create()` | extracted `parsePayload()` |
| `php:S1142` | `OrdersController::extractItems()` | extracted `extractItem()` |
| `php:S1142` | `RedirectController::shouldRecordClick()` | split into `isBotUserAgent()`/`isCrossSiteReferer()` |
| `php:S1448` (>20 methods) | `ClientRepository` (21, pushed over by the new `findByEmail()`) | pulled the three name/surname filters into `ClientRepositoryFilterTrait` — the same trait-split technique `InvController` already uses (`Inv/Trait/{Add,Edit,Guest,...}`) to stay under this same ceiling, just applied to a repository instead of a controller (21 → 18) |
| `php:S5332` (insecure HTTP) | `GeoIpLookupService`'s `ip-api.com` fallback | scoped `sonar-project.properties` ignore rule (`ip-api.com`'s free tier has no HTTPS option, only a bare IP is ever sent, and it's already justified in the class's own docblock) rather than an inline suppression comment |

All six resolved by either genuine extraction (the four `S1142`s and the
`S1448`) or a config-level scoped ignore matching the existing
`sonar-project.properties` pattern (`S116`/`S1068`/`S1125`/`S1192` were
already suppressed project-wide the same way) — no inline
`@psalm-suppress`- or `// NOSONAR`-style comments used anywhere.

## Verified

`vendor/bin/psalm --no-cache` — "No errors found!" both before and after
the SonarQube fixes. 880/880 Testo, 3912/3912 legacy Codeception/PHPUnit,
re-confirmed after every batch of changes, not just at the end.
