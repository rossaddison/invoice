# System Updates — PHP Version Check

A new "System Updates" Settings tab that checks whether a newer PHP patch
release is available for the running major.minor branch, and shows
copyable (never executed) upgrade instructions for four environments.

## Why

CVEs against PHP itself are a real, ongoing risk (see
[docs/ALPINE_LINUX_CVE_2026_31431.md](ALPINE_LINUX_CVE_2026_31431.md) for a
recent example), and this app never surfaced whether the running PHP
version was behind. This closes that gap.

## Placement, detection, and button behavior — decided up front

Three design questions were resolved before writing any code:

1. **Where does it live?** A dedicated Settings tab ("System Updates"),
   not the debug-mode FAQ page and not a site-wide banner — least new
   exposure surface, matches how every other admin-only setting already
   lives behind the existing `setting/tabIndex` route's `EDIT_INV` gate.
2. **How is "latest version" learned?** A background console command
   (`php yii system/check-php-version`), cached in the `setting` table —
   matches this repo's existing pattern for external checks
   (`peppol-check`, `as4/monitor`) rather than a live call on every page
   load. A "Check Now" button also exists for on-demand refresh, triggered
   only by an explicit authenticated click.
3. **What do the four platform buttons do?** Show copyable command blocks
   only — nothing is ever executed server-side from an HTTP request. This
   was a hard requirement, not a preference: running real shell commands
   triggered by a web request would undo everything the CSP hardening work
   this session was about (see
   [CSP_INLINE_HANDLER_SWEEP_GAPS.md](CSP_INLINE_HANDLER_SWEEP_GAPS.md)).

## Architecture

- **`App\Invoice\System\PhpVersionCheckService`** — the single source of
  truth, called by both the console command and the controller action (DDD
  Application Service pattern, consistent with
  [SonarQube S107 work](SONARQUBE_S107_APPLICATION_SERVICE.md)). Calls
  `https://www.php.net/releases/index.php?json&version={major.minor}`,
  compares against `PHP_VERSION` via `version_compare()`, persists the
  result to three `setting` rows (`system_php_latest_patch_version`,
  `..._is_security`, `..._checked_at`, plus `..._error` for graceful
  degradation), and exposes both `check()` (hits the network) and
  `getCached()` (reads only).
- **`App\Invoice\System\Console\CheckPhpVersionCommand`** —
  `php yii system/check-php-version`, thin wrapper around the service for
  cron use.
- **`SettingController::checkPhpVersionNow()`** — the "Check Now" button's
  target, `GET /invoice/setting/checkPhpVersion`, gated by the same
  `Permissions::EDIT_INV` every other settings-tab route uses. Redirects
  back to `setting/tabIndex?active=system-updates` with a flash message.
- **`SettingRepositoryInterface`** gained `withKey()` and `save()`
  (previously only `getSetting()`) — the service needs write access, and
  `SettingRepository` is `final` so PHPUnit can't mock it directly without
  going through the interface. Confirmed `SettingRepository` is the
  interface's only implementor before extending it, so this was low-risk.

## Frontend

No new TypeScript file — extended the existing `data-actions.ts` delegation
module (see [CSP_INLINE_HANDLER_SWEEP_GAPS.md](CSP_INLINE_HANDLER_SWEEP_GAPS.md))
with two small, generic, reusable primitives rather than writing
feature-specific JS:

- `data-action="toggle-panel" data-target="#selector"` — shows/hides any
  element by CSS selector.
- `data-action="copy-to-clipboard" data-copy-target="#selector"` — copies
  another element's `textContent` via the Clipboard API, flashes the
  button text to "Copied!" for 1.5s.

Both are page-agnostic and reusable by any future feature needing an
accordion toggle or a copy button, not just this one.

## Testing

- `Tests/Unit/Invoice/System/PhpVersionCheckServiceTest.php` — 9 tests:
  outdated/security/up-to-date detection, settings persistence (both the
  create-new-row and update-existing-row branches), graceful degradation on
  HTTP failure and malformed JSON, and both `getCached()` paths. Uses
  Guzzle's `MockHandler`/`HandlerStack` (no precedent for this existed in
  the codebase before — established here) plus `createStub()` (not
  `createMock()`) since only return values are configured, no invocation
  expectations — PHPUnit 13 flags `createMock()` without `->expects()` as
  a notice, and this codebase treats any non-pass test marker as a
  failure.
- `data-actions.test.ts` — 4 new cases for the two new primitives.
- Full suite after this change: PHPUnit 3,702 (Unit) + 69 (Functional/
  Integration/PHPUnit) + 9 (this feature) all passing, zero notices/
  warnings. Vitest 135/135. Psalm clean (fixed 4 real errors surfaced by
  the interface extension and a nullable-string concatenation, not
  pre-existing).

## Verified

- `php yii router/list` — new route registers correctly.
- `php yii system/check-php-version` — ran end-to-end against the real
  php.net API in this dev sandbox: DI resolved correctly, the HTTP call
  succeeded and correctly parsed a real security release, and it only
  failed at the final database write because this particular sandbox has
  no reachable MySQL server — confirmed unrelated to this feature by
  checking that a DB-independent command (`router/list`) still worked fine.
- **Not verified**: the rendered Settings tab in an actual browser, for the
  same reason (no local DB to boot a full page against in this sandbox).
  Worth a visual check on a real WAMP/Alpine deployment before relying on
  it.
