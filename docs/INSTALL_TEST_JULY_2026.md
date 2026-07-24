# Fresh Install Test — Signup, RBAC Roles, Mandatory Admin 2FA

## Why

The install steps in the README (`composer update` → `BUILD_DATABASE=true` → `npm i`
→ signup) had never been run end-to-end on a genuinely fresh checkout — only ever
incrementally, on the long-lived working copy. This pass cloned the repo into a new
sibling folder (`invoice-installtest`), pointed it at a brand-new MySQL schema
(`yii3_i_installtest`), and drove the whole install plus the first admin/observer
signup and the first admin login purely over HTTP (cookie jar + CSRF token scraped
from each page, no browser) to see what a new installer actually hits.

## `composer update` — clean

No issues. The post-install `composer audit` step failed in this sandbox only because
outbound network access wasn't available; harmless and unrelated to the app itself.

## `npm i` is not optional — it gates the very first page load

The README lists "Installing npm_modules" as a separate section after the database
step, which reads as optional/later. It isn't. Before `node_modules` exists, the very
first HTTP request (even just the database-build request with `BUILD_DATABASE=true`)
throws:

```
Yiisoft\Assets\Exception\InvalidConfigException: The sourcePath to be published does
not exist: ...\node_modules/bootstrap/dist/css
  at vendor/yiisoft/assets/src/AssetPublisher.php:96
```

`AssetManager` publishes CSS/JS straight out of `node_modules` (`BootstrapCssOnlyAsset`
etc.) on every page render, including the login/signup screens — there is no
prebuilt-assets fallback. `npm i` must run before the app is loaded at all, not just
before doing frontend work.

## `BUILD_DATABASE=true` — confirmed working

First request against the fresh schema built all 84 Cycle ORM tables in one pass
(`PhpFileSchemaProvider::MODE_WRITE_ONLY`). Flipping `BUILD_DATABASE` back to `false`
afterward left the schema in normal read/write mode with no further issues.

## Signup — first user → admin, second → observer, confirmed at the DB level

Signed up two users (`Signup[login]`/`Signup[email]`/`Signup[password]`/
`Signup[passwordVerify]`, `_csrf` scraped from the signup page). Neither had
`SYMFONY_MAILER_*` configured, so both hit the app's SMTP-connect-refused path and
landed on `/signupfailed` — but the DB shows the signup itself fully succeeded before
the mail step ran:

- `user`: both rows created.
- `yii_rbac_assignment`: `admin` → user 1, `observer` → user 2 (`repoCount() == 1`
  check in `SignupController::signup()` correctly fires only for the very first user).
- `user_inv`: both rows created with `active = 0` (no working SMTP means the
  email-verification link was never clickable).

This matches the README's existing "you will still be able to login" note for the
admin case — confirmed accurate, not just aspirational. `user_rbac_link` (the
userinv↔RBAC bridge table) is empty immediately after signup because
`assignRoleAndVerify()` runs before the `user_inv` row exists yet; this is expected
and self-heals via `syncIfEmpty()` on first `/invoice` load, per the existing bridge
design.

## Admin login — 2FA is mandatory regardless of the `enable_tfa` setting

Not previously documented anywhere in the README: **the first admin login is always
routed through TOTP setup**, even with the global `enable_tfa` setting off —
`AuthController::resolveLoginResponse()` forces the 2FA path for any admin user.
Logging in as `admin` redirected to `/showSetup` (QR code + secret, page links out to
`getaegis.app`), not straight to the dashboard.

Verified this without a phone by generating real TOTP codes from the scanned secret
using the app's own `spomky-labs/otphp` library (`OTPHP\TOTP::create($secret)->now()`)
— the same algorithm Aegis or any other TOTP app implements. POSTing that code to
`/verifySetup` completed enrollment and redirected to `/verifyLogin`; a second,
freshly-generated code POSTed there completed login and landed on `/invoice` (200,
correct title, admin's own name rendered in the page). DB confirms `tfa_enabled = 1`
and a non-empty `totp_secret` for the admin row afterward.

## Windows gotcha: antivirus false positive on a fresh clone

Immediately after `git clone`, `public/index.php` vanished from the working tree
(`git status` showed it deleted, and re-checking it out failed with
`Permission denied`) — AVG's heuristic scanner (`IDP.generic`) was silently deleting
the freshly-written file, presumably because a new `index.php` appearing under a
`www`-style folder trips a generic web-shell heuristic. No other file in the clone
was affected. Adding an AVG exception for the project folder resolved it immediately.
Worth knowing if a fresh Windows install inexplicably 500s on `public/index.php`
missing right after cloning/extracting.
