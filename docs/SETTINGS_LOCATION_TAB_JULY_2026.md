# Settings "Location" Tab — Live GPS Tester + Capture Placeholder — July 2026

## Background

Came out of a question about exposing the current device's GPS location on
the `inv/index` breadcrumb. That's the wrong home for it: GPS is a
browser-only capability (`navigator.geolocation`, nothing PHP can read
server-side), it needs a real permission prompt every time, and cramming a
prompt-driven widget into a grid's breadcrumb doesn't fit the rest of that
UI. Settings already has a precedent for exactly this shape of thing — the
`fph_client_*` browser-fingerprint fields on the General tab are populated
the same way (`SettingsHandler.handleFphGenerateClick()` in
`settings.ts`) — so a new dedicated tab was the natural fit instead.

Also ties into an earlier, still-unbuilt idea: a worker/manager status
workflow where the manager's GPS gets captured *at the moment they release
an invoice to "sent"*, alongside the worker's name, as a proof-of-completion
record. The worker-allocation half of that idea shipped earlier this month
(`docs/HOMECARE_WORKER_ALLOCATION_JULY_2026.md`); the GPS-capture-at-send
half still doesn't exist. This tab is split deliberately into two pieces
that reflect that: one that's fully working today, and one that's an
honest placeholder for later.

## 1. Live tester — proves geolocation works, stores nothing

`resources/views/invoice/setting/views/partial_settings_location.php`'s
first card has a "Test My Location" button
(`SettingsHandler.handleGeolocationTestClick()` in `src/typescript/settings.ts`)
that calls `navigator.geolocation.getCurrentPosition()` directly and
renders latitude/longitude/accuracy into a result `<div>` — nothing is
submitted to the server. Before even asking the browser, it checks two
preconditions and fails with a specific message rather than guessing:

- **Not supported**: `'geolocation' in navigator` is false (old or
  stripped-down browser).
- **Not a secure context**: geolocation requires HTTPS, and browsers only
  treat `localhost`/`127.0.0.1` as an exception to that — a custom local
  hostname like this project's own `invoice.myhost` over plain HTTP does
  **not** qualify, so testing this locally only works via `localhost`
  itself, not the usual WAMP vhost. Production (`yii3i.online`) already
  serves HTTPS, so it's unaffected there.

If the browser does ask and the user responds, the three
`GeolocationPositionError` codes are handled distinctly (`PERMISSION_DENIED`,
`POSITION_UNAVAILABLE`, `TIMEOUT`) rather than a single generic failure
message.

## 2. `capture_gps_on_send` — an honest placeholder, not a real feature yet

The second card is a plain Yes/No setting, off by default, following the
exact pattern `homecare_auto_invoice_enabled` already uses elsewhere
(same select-with-two-options shape, same `$s->infoIcon()` tooltip
mechanism). Both its on-page description and its
`SettingTooltipTrait::tooltipArrayC1()` tooltip entry say plainly that
turning it on currently does nothing — there's no started/completed
invoice status, no worker-scoped `inv/index` visibility beyond the
allocation column already shipped, and no code path that reads this
setting yet. It exists now so the eventual feature has an on/off switch
to build against, not to imply the feature is live.

Seeded via `InvoiceInstallTrait.php` (`'capture_gps_on_send' => 0`) like
every other setting's default — new/existing installs alike pick it up
without a manual DB step, since a missing setting row also just
auto-creates itself the first time the Settings form is submitted
(`SettingController::saveSubmittedSettings()`'s existing fallback), same
as any other setting key.

## Files touched

- `resources/views/invoice/setting/views/partial_settings_location.php` (new)
- `resources/views/invoice/setting/tab_index.php` — new `location` tab entry
- `src/Invoice/Setting/SettingController.php` — renders the new partial
- `src/Invoice/Setting/Trait/SettingTooltipTrait.php` — tooltip entry
- `src/Invoice/Trait/InvoiceInstallTrait.php` — settings seed default
- `resources/messages/en/app.php` — new translation keys
- `src/typescript/settings.ts` — `handleGeolocationTestClick()`, added to
  the existing `SettingsHandler` class alongside `handleFphGenerateClick()`

## Verification

- Full-project `vendor/bin/psalm --no-cache` clean.
- `vendor/bin/testo --suite=Unit` — 242 tests, unaffected.
- `esbuild` production bundle builds clean.
- Not verified: an actual browser click-through of the permission
  prompt/result rendering — no running web server was available in this
  environment. Worth trying live once deployed, since geolocation prompt
  behavior varies meaningfully across browsers and OSes in practice.
