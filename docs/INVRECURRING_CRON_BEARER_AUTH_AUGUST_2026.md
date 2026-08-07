# InvRecurring Cron Endpoint — Bearer Token Auth — August 2026

## Summary

`invrecurring/cron` — the HTTP endpoint an external cron scheduler hits to
generate due recurring invoices and send Telegram balance reminders — used
to authorize itself by comparing a `cron_key` URL query parameter against
a stored setting, by hand, inside the controller action. It now uses real
HTTP Bearer authentication (`Authorization: Bearer <cron_key>`), enforced
by `Yiisoft\Auth\Middleware\Authentication` before the action ever runs.

This is a live, working example of the distinction drawn in
[docs/WWW_AUTHENTICATE_VS_SESSION_AUTH_AUGUST_2026.md](WWW_AUTHENTICATE_VS_SESSION_AUTH_AUGUST_2026.md):
a program (a cron scheduler), not a person, making a single standalone
request — exactly the shape `WWW-Authenticate`-style challenge auth was
built for, and the one endpoint in this app actually shaped like that.

## Before

```php
$params = $request->getQueryParams();
$cronKey = (string) ($params['cron_key'] ?? '');
if ($cronKey === '' || $cronKey !== $this->sR->getSetting('cron_key')) {
    return $this->factory->createResponse(Json::encode(['success' => false, 'error' => 'Forbidden']));
}
```

```
GET /invrecurring/cron?cron_key=d4e25cacd9843c0609b2d6cb090cac26492b04f088e1a693
```

Real problems with this shape, not just style:

- **The secret travels in the URL** — shell history, the cron daemon's own
  job log, and the web server's access log all end up with it in plain
  text, indefinitely.
- **`!==` is not constant-time** — a timing side channel, however
  impractical to exploit over a real network.
- **Wrong HTTP status on failure** — a bad key returned `200 OK` with
  `{"success":false,"error":"Forbidden"}`, not a `4xx`. Anything that
  actually checked the status code (rather than parsing the body) would
  have treated an unauthorized request as a success.
- **No standard machine-readable retry signal** — nothing told a
  well-behaved HTTP client *how* to authenticate; the exact query
  parameter name and expected value existed only in this app's own code
  and whatever documentation someone wrote down for the cron job.

## After

```php
// config/common/routes/routes-inv-recurring.php
Route::get('/invrecurring/cron')
    ->middleware(Authentication::class)
    ->action([InvRecurringController::class, 'cron'])
    ->name('invrecurring/cron'),
```

```
GET /invrecurring/cron
Authorization: Bearer d4e25cacd9843c0609b2d6cb090cac26492b04f088e1a693
```

The controller action no longer contains any auth check at all — an
unauthenticated request never reaches it. `Authentication` middleware
intercepts it first and, backed by `HttpBearer` (which implements
`AuthenticatorWithChallengeInterface`), returns a real challenge response
automatically:

```
HTTP/1.1 401 Unauthorized
WWW-Authenticate: Bearer realm="api"
```

Live-curled against this app's own local instance, before vs. after:

```
$ curl -i http://invoice.myhost/invrecurring/cron
HTTP/1.1 401 Unauthorized
WWW-Authenticate: Bearer realm="api"

$ curl -i -H "Authorization: Bearer wrong-token" http://invoice.myhost/invrecurring/cron
HTTP/1.1 401 Unauthorized
WWW-Authenticate: Bearer realm="api"

$ curl -i -H "Authorization: Bearer d4e25cac...693" http://invoice.myhost/invrecurring/cron
HTTP/1.1 200 OK
{"success":true,"created":0,"reminded":0}
```

## What's new

- **`CronIdentity`** (`src/Invoice/InvRecurring/CronIdentity.php`) — a
  trivial `IdentityInterface` implementation; there's no real user account
  behind a cron job, just a fixed marker `Authentication` needs to be
  non-null.
- **`CronTokenRepository`** (`src/Invoice/InvRecurring/CronTokenRepository.php`)
  — `IdentityWithTokenRepositoryInterface` backing `HttpBearer`. Reuses the
  existing `cron_key` setting value unchanged (same value, same
  install-time generation via `Random::string(32)`) — only the transport
  changes. Compares with `hash_equals()`, not `!==`.
- **`config/web/di/cron-auth.php`** — the one, narrow
  `AuthenticatorInterface` binding this app now has, wired to `HttpBearer`
  + `CronTokenRepository`. Deliberately not app-wide in effect: it only
  does anything on the one route that explicitly applies
  `->middleware(Authentication::class)`.

This is intentionally **not** built on this app's general-purpose
Token/Identity system (`App\Auth\TokenRepository`, backed by real database
tables) — a cron job isn't a user account, and reusing that machinery
would mean inventing a fake user to own the token for no real benefit.

## Why this didn't ship as part of the Authentication DI crash fix

That fix ([docs/AUTHENTICATION_DI_CRASH_FIX_AUGUST_2026.md](AUTHENTICATION_DI_CRASH_FIX_AUGUST_2026.md))
removed `Authentication::class` everywhere it was applied, because it had
been referenced across most of the app's routes with no
`AuthenticatorInterface` binding at all — unconditionally unconstructable.
This endpoint is the one legitimate exception that fix's own writeup
predicted: a route where the caller genuinely is a program, not a person,
and where a real binding now exists to back it. Migrating it was
deliberately left for a separate, focused change rather than folded into
an urgent production-outage fix.

## Verification

- Full-project Psalm (`vendor/bin/psalm --no-cache`): no errors found.
- Full Testo suite: 760/760 passing (4 new tests in
  `Tests/Testo/Invoice/InvRecurring/CronTokenRepositoryTest.php`).
- Full PHPUnit suite: 3,877/3,877 passing (23 pre-existing notices only).
- Live-curled all three cases above against the running local site.
