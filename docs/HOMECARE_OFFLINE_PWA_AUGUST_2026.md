# HomeCare Offline Invoice Viewer (PWA) — August 2026

> See [`HOMECARE_OFFLINE_PWA_DATA_FLOW_AUGUST_2026.md`](HOMECARE_OFFLINE_PWA_DATA_FLOW_AUGUST_2026.md)
> for a diagram of the mechanism below — the two client-side stores
> (Cache Storage vs. IndexedDB) and why `inv/guest` itself is never
> cached.

## Why

HomeCare field workers use `inv/guest` (the "worker" RBAC role, see
`HOMECARE_WORKER_ALLOCATION_JULY_2026.md`) to see the invoices allocated to
them for the day. Broadband/mobile signal at a client's home is sometimes
unreliable or down entirely, and a worker previously had no way to see
invoice/client details once offline. This adds a "download for offline"
capability: pull down currently-allocated invoices while still connected,
then browse that data with zero connectivity.

Two scoping decisions, confirmed directly with the user before building:

- **View only.** No new data is captured or written while offline — this is
  a read-only reference tool. There is nothing to "upload" back once
  connectivity returns; instead, the downloaded copy silently refreshes
  itself against the server whenever the worker next has connectivity.
- **Progressive Web App**, not a separate native/hybrid app — an
  installable, offline-capable version of the existing web app.

## Architecture

**App-shell model**, not a cached copy of `inv/guest` itself. `inv/guest` is
a fully server-rendered page with a live CSRF token and session-scoped
state — a service worker serving a stale cached copy of that HTML risks
CSRF mismatches and stale-permission data being shown as current. Instead:

- The *shell* — a small, mostly-static page
  (`resources/views/invoice/inv/offline.php`, route `inv/guest/offline`) and
  its own small dedicated JS bundle — is precached by the service worker, so
  it always loads, online or off.
- The *data* is fetched once via a real JSON API call
  (`GET /client_invoices/offline-data`, `Guest.php::guestOfflineData()`)
  while online, stored in IndexedDB, and rendered from IndexedDB whenever the
  shell is opened.

### New files

- `public/manifest.json` + `public/icon-192.png` / `public/icon-512.png` —
  web app manifest and placeholder icons (a plain "Y3i" monogram on the
  app's existing blue — `#1e73b8` — generated via PHP's GD extension, not
  derived from `public/img/logo.svg`, which is legacy InvoicePlane branding
  this project has moved away from). Flagged as placeholders; swap the two
  PNGs for a real design whenever one exists.
- `src/typescript/sw.ts` → `public/sw.js` (own esbuild entry, see
  `package.json`'s `build:typescript:sw` — a service worker runs in its own
  global scope and can't join the main IIFE bundle). Precaches only the
  offline shell page and its JS; every other request passes straight
  through to the network, untouched.
- `src/typescript/homecare-offline-db.ts` — shared IndexedDB helper (one
  object store, one "current snapshot" record, replaced wholesale on every
  download — nothing to merge/reconcile for a read-only tool).
- `src/typescript/homecare-offline.ts` — runs on `inv/guest` (part of the
  main bundle): registers the service worker, wires the "Download for
  Offline" button, and silently re-downloads in the background on every
  page load while online (this is what fulfils "refresh once broadband is
  back" without any actual write-back to the server).
- `src/typescript/homecare-offline-shell.ts` → `public/homecare-offline-shell.js`
  (own esbuild entry, `build:typescript:homecare-shell` — deliberately not
  part of the main ~280kb IIFE, to keep what the service worker has to
  precache small). Reads IndexedDB and renders the invoice list/detail into
  the shell page's container.
- `tsconfig.sw.json` — `sw.ts` needs `ServiceWorkerGlobalScope` typing,
  which conflicts with the main `tsconfig.json`'s DOM lib (`self` means
  different things in a window vs. a worker) — `sw.ts` is excluded from the
  main config and type-checked separately (`npm run type-check` runs both).
- `Tests/Testo/Invoice/Inv/GuestOfflineTest.php` — covers
  `guestOfflineData()`/`guestOffline()` against a minimal harness that mixes
  in the real `Guest` trait (avoids constructing a full `InvController`,
  which has 4 dependency-group constructor args covering dozens of
  unrelated collaborators).

### Modified files

- `src/Invoice/Inv/Trait/Guest.php` — two new actions,
  `guestOfflineData()`/`guestOffline()`, both 404-ing for anyone without a
  linked `Worker` (an ordinary client guest has nothing worker-scoped to
  download). `guestOfflineData()` reuses the exact same
  `resolveGuestAccess()`/`repoWorkerVisible()` scoping `guest()` itself
  already uses. **Deliberately excludes every amount/price field** from the
  JSON payload — matches the existing `Permissions::VIEW_PAYMENT`
  restriction already applied to a worker on the live `inv/guest` grid (the
  worker RBAC role doesn't have it — see `resources/rbac/items.php`).
- `config/common/routes/routes-inv.php` — two new routes,
  `inv/guest/offlineData` / `inv/guest/offline`, same
  `RoutePermission::check(Permissions::VIEW_INV)` middleware as `inv/guest`.
- `resources/views/layout/guest.php` — `<link rel="manifest">` + a
  `theme-color` meta tag (harmless/generic for both plain client guests and
  worker guests — CSP already permits this: `manifest-src 'self'` /
  `worker-src 'self'`, `config/web/params.php`).
- `resources/views/invoice/inv/guest.php` — a "📥 Download for Offline"
  button and a "📱 View Offline Copy" link, both worker-only (`$worker !==
  null`, the same gating already used for the existing do-not-send column).
- `src/typescript/index.ts` — wires in `homecare-offline.ts`.
- `package.json` — two new esbuild scripts; `build:typescript:prod` and
  `type-check` both updated to run them.

## Deployment-path handling

Every URL this feature references (`/manifest.json`, `/sw.js`,
`/homecare-offline-shell.js`, `/client_invoices/offline-data`) is a plain
root-relative path — deliberately **not** computed from any alias. Checked
directly: `config/common/params.php` defines `'@baseUrl' => ''`, and
`'@assetsUrl' => '@baseUrl/assets'` (i.e. plain `/assets`) already works
correctly in this app's production deployment (every CSS/JS asset loads
fine there today) — so root-relative paths are the proven-correct
convention for this deployment, not something needing a computed prefix.

## ⚠️ What's still a placeholder / needs a real device check

- The two app icons are a plain generated placeholder monogram — replace
  with a real design when one exists.
- Verified locally via curl (manifest/service-worker/icons/shell JS all
  serve `200`; the two new routes cleanly `404` for an unauthenticated
  request rather than crashing) and via `GuestOfflineTest`'s worker-scoped
  mocks (correct JSON shape, amount fields absent, 404 for a non-worker
  guest). **Not yet verified against a real device with actual airplane
  mode** — desktop devtools' offline throttle doesn't fully exercise service
  worker installability/registration edge cases on mobile Safari/Chrome, and
  that's the actual target platform for this feature.
- `npm run type-check`'s main `tsconfig.json` pass was already broken
  before this feature (pre-existing `moduleResolution`/`baseUrl`/`paths`
  options removed by a newer installed TypeScript version) — confirmed via
  `git diff` that this feature's own change to that file is a single
  `exclude` line, not the cause. Not fixed here (out of scope, unrelated to
  this feature, and not something to touch speculatively). `sw.ts`'s own
  `tsconfig.sw.json` type-checks cleanly in isolation.

## Verification

- Full-project Psalm (`vendor/bin/psalm --no-cache`): **no errors found**.
- Full Testo suite (735 tests) and full PHPUnit suite (3,877 tests): all
  passing, including 5 new `GuestOfflineTest` tests.
- `npm run build:typescript:prod` (now includes the two new bundles) and
  `npx tsc --noEmit -p tsconfig.sw.json`: both clean.
- Live-curled the new static assets and routes against the running local
  site (`http://invoice.myhost`): `manifest.json`/`sw.js`/
  `icon-192.png`/`homecare-offline-shell.js` all `200`;
  `client_invoices/offline`/`client_invoices/offline-data` cleanly `404`
  for an unauthenticated request.
