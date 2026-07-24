# Web-Based Setup Wizard — `/install`

## Why

The prior install path was entirely manual: hand-edit `.env` for DB credentials,
flip `BUILD_DATABASE=true`, load a page to trigger table creation, remember to flip
it back, then find the signup page yourself — with no in-app feedback at any step
(see [Fresh Install Test](INSTALL_TEST_JULY_2026.md) for what that actually looks
like end-to-end). `/install` turns that into a numbered-step wizard (Requirements →
Database → Build Tables → Create Admin) that a brand-new installer sees in the
browser, reusing the exact `/signup` → `/login` → mandatory-2FA flow already proven
to work rather than rebuilding it.

## Architecture

`App\Install\Controller\InstallController` (`config/common/routes/routes-install.php`)
deliberately has **no Cycle-ORM/DI-backed dependency in its constructor** — every
database check goes through `App\Install\DatabaseProbe`, a raw-PDO helper, so the
controller can render safely before the database is configured, reachable, or built.
State (which step to show) is recomputed from scratch on every request in
`determineState()`, resumable from wherever an installer left off:

1. **Requirements** — `App\Install\RequirementsConfig::checks()` (shared with the
   CLI `requirements.php`, so the two never drift apart) via the existing
   `RequirementsChecker::check()->getResult()`, consumed directly rather than its
   HTML `render()`.
2. **Database** — form posts to `/install/test-connection` (AJAX, no `.env` write)
   and `/install/database` (writes `DB_HOST_IP_ADDRESS`/`DB_NAME`/`DB_USERNAME`/
   `DB_PASSWORD` + `BUILD_DATABASE=true` via `App\Install\EnvFileWriter`, which
   rewrites individual `KEY=value` lines in place). Reuses the raw-PDO
   create-database-if-missing technique already proven in
   `src/Command/InstallCommand.php::createDatabase()`.
3. **Build Tables** — `/install/build-tables` resolves a Cycle-ORM-backed
   dependency, which is what actually triggers `PhpFileSchemaProvider`'s
   `MODE_WRITE_ONLY` schema build as a side effect; confirms the `user` table now
   exists, then flips `BUILD_DATABASE` back to `false`.
4. **Handoff** — links straight to the real `/signup`, unchanged. First signup
   becomes admin automatically (existing `SignupController` logic); 2FA is already
   mandatory for admins regardless of the global setting (unchanged
   `AuthController` behavior) and is what actually happens on first login.

`isAlreadyInstalled()` (an admin user already exists) guards all four routes, not
just the page — so the wizard can't be re-triggered against a live database once a
site is installed, and self-heals `BUILD_DATABASE` back to `false` even if the
tables got built as a page-render side effect before the "Build Tables" button was
ever clicked (see below).

## Bugs found and fixed while building this

Building a page that has to render *before* any table exists surfaced three
previously-latent issues — none specific to the wizard, all now fixed:

- **`App\ViewInjection\CommonViewInjection`** and **`LayoutViewInjection`** — both
  globally registered for every page render — unconditionally query the `company`
  table (and `SettingRepository::debugMode()` unconditionally writes a settings
  row). Every page in the app already assumed the database is always up; a missing
  database or table 500'd the *entire* app, not just `/install`. Fixed by catching
  the failure and falling through to each method's existing "no company row found"
  defaults.
- **`SettingRepository::loadSettings()`** — same problem, one level deeper:
  `getSetting()` (dozens of call sites app-wide) always calls this first. Added a
  `$loadSettingsAttempted` guard alongside the existing `$settingsArray !== []`
  early-return so a failed connection is only attempted once per request, not once
  per `getSetting()` call.
- **`requirements.php`** was fatal-broken before this pass: its
  `require_once 'vendor/yiisoft/requirements/...'` pointed at a path that doesn't
  exist (the package is actually installed as `rossaddison/requirements`, still
  under the `Yiisoft\Requirements` namespace and fully composer-autoloadable) — so
  running it at all threw immediately. Separately, its memory-limit check called
  `checkPhpIniOn('memory_limit')`, which only recognizes boolean-style ini values
  (`'on'`/`1`) and therefore always evaluated `false` against a size string like
  `1024M` — the mandatory memory check could never pass regardless of the real
  limit. Both fixed in `RequirementsConfig` (shared with `/install`); a third,
  vendor-side bug (`checkMaxExecutionTime()`'s `ini_get('max_exection_time')` typo,
  and the web view's `$summary['errors'] !== []` comparison against an int,
  always true) was left alone since it lives in the vendor package itself — the
  wizard sidesteps it entirely by consuming `getResult()` directly.

## Deliberately out of scope

- `SignupController`/`AuthController` and their views — untouched; the wizard hands
  off to them as-is.
- `src/Command/InstallCommand.php` — stays as the CLI alternative for headless
  installs; only its DB-creation technique was reused, not its code (its own
  `parseDatabaseConfig()` is separately stale/broken — regexes a `params.php`
  switch-statement shape that no longer exists — flagged but not fixed here).
- No rate-limiting middleware on the new routes — the self-lock guard is the actual
  security control (an installed system can't be re-triggered); an easy addition
  later if wanted (`RateLimiter::perIp`, matching `/signup`/`/login`).
- English-only UI text for now — hasn't gone through this app's translation-key
  rollout (`resources/messages/*/app.php`).
