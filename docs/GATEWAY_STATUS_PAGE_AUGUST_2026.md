# Public Payment Gateway Status Page (August 2026)

## Why

The project supports 8 payment gateways/methods (Stripe, Braintree, Mollie,
Amazon Pay, Adyen, GoCardless, Open Banking, BACS), but nothing public showed
which ones actually work, what SDK version they're pinned to, or when they
were last verified. This adds a public `/gateway-status` page — linked from
the homepage — plus the machinery to keep it honest without much manual
upkeep, and lays the groundwork for adding more region-specific gateways
over time (Asia is the current priority — see Robokassa below).

## Data flow

```
resources/gateway-status/gateways.json   (human-edited source of truth, PR-reviewable)
        │
        ├─ php yii gateway-status/rebuild          (composer.lock → sdk_version/last_updated)
        ├─ php yii gateway-status/check-sandboxes  (sandbox API ping → sandbox_tested_at/status)
        │
        ▼
gateway_status SQLite table   (Cycle ORM entity, own database, BUILD_DATABASE=true schema sync)
        │
        ▼
SiteController::gatewayStatus()  →  resources/views/site/gateway-status.php
```

`gateways.json` is the only file a human edits directly. Field ownership:

| Field | Who writes it |
|---|---|
| `sdk_version`, `last_updated` | `gateway-status/rebuild`, from `composer.lock` — `last_updated` only bumps when the resolved version actually changed |
| `sandbox_tested_at`, `sandbox_status`, `sandbox_last_error` | `gateway-status/check-sandboxes` |
| `name`, `regions`, `notes`, `live_tested_at`, `sandbox_env_var` | Human only — never touched by either command |

`live_tested_at` is deliberately excluded from all automation. This project's
own precedent (`docs/PAYMENT_GATEWAY_LIVE_TESTING_JULY_2026.md`) shows live
gateway testing has always been manual and browser-driven; scheduling real
payment-flow tests weekly would be reckless. It's a human-only field, full
stop.

## Why a second, Cycle-ORM-managed SQLite database

The initial design read/wrote the SQLite file directly via plain PDO,
deliberately decoupled from Cycle ORM to avoid touching the app's shared
database config. On reflection this was reversed: the table is created and
synced the same way every other entity's table in this app is — through
Cycle's `#[Entity]` attribute plus the existing `BUILD_DATABASE=true`
schema-sync convention — rather than via hand-rolled `CREATE TABLE` SQL.

`config/common/params.php`'s `dbal` config gained a second database
(`gateway_status` → a new `sqlite` connection, `Cycle\Database\Config\SQLiteDriverConfig`
pointing at `resources/gateway-status/gateway-status.sqlite`) alongside the
existing `default`/`mysql` one — the existing MySQL wiring is untouched.
`src/Infrastructure/Persistence/GatewayStatus/GatewayStatus.php` is the
first entity in this app to use `#[Entity(database: 'gateway_status')]` —
every other entity implicitly uses the default MySQL database. Alongside the
unique index on `gateway_key`, each date column (`last_updated`,
`sandbox_tested_at`, `live_tested_at`) has its own non-unique index, since
each is a plausible independent filter/sort key (e.g. "which gateways were
sandbox-tested in the last week") and a composite index wouldn't serve all
three equally well.

**Important shared-schema-sync risk**: Cycle's schema compiler runs as one
pipeline pass across *every* configured database together, triggered lazily
on the first request that resolves `SchemaInterface`/`ORMInterface` after
`BUILD_DATABASE=true`. A misconfigured SQLite path/permissions doesn't just
fail this feature — it throws during that shared resolution and can break
*every page in the app*, MySQL-backed ones included, until fixed. This was
verified locally before shipping: `BUILD_DATABASE=true` was flipped on, the
`gateway_status` table was confirmed created, the full PHPUnit suite (3,877
tests) was run to confirm the MySQL-backed schema was unaffected, then the
flag was flipped back to `false` — the same discipline the project's
existing `BUILD_DATABASE` convention already calls for, just with a higher
stake now that two databases share one schema pass instead of one.

Once the schema exists (it's committed as part of `gateway-status.sqlite`,
same as any other generated build artifact this repo already commits),
routine runs of `gateway-status/rebuild`/`check-sandboxes` — including the
scheduled GitHub Actions workflow — never need `BUILD_DATABASE=true` at all;
it's only needed locally when the entity's own schema changes.

## Console commands

- `php yii gateway-status/rebuild` — resolves each gateway's SDK version from
  `composer.lock`, rewrites `gateways.json`, syncs every row into the
  database.
- `php yii gateway-status/check-sandboxes` — for each gateway with a
  `sandbox_env_var` set in `gateways.json`, reads that named environment
  variable; if unset, skips without failing (this is what makes rollout
  incremental per gateway/region). If set, makes one confirmed
  side-effect-free sandbox API call and records the result.

Only **Stripe** (`balance->retrieve()`), **Mollie** (`methods->allEnabled()`),
and **GoCardless** (`creditors()->list()`) have a confirmed, genuinely
read-only sandbox call wired up so far — all three verified against this
app's own vendored SDK source before wiring them in. Braintree, Adyen, and
Amazon Pay ship with `sandbox_env_var: null` until a safe no-side-effect call
is confirmed for each (a client-token fetch, for instance, isn't actually
read-only). Open Banking and BACS aren't classic gateway-SDK pings and stay
`sandbox_env_var: null` permanently.

## GitHub Actions secrets

Naming convention: `{GATEWAY}_SANDBOX_{CREDENTIAL}`, deliberately separate
from this app's own production credentials (which live encrypted in the
`Setting` table, decrypted at runtime via `Cryptor` — see
`docs/PAYMENT_GATEWAY_LIVE_TESTING_JULY_2026.md`). Currently wired into
`.github/workflows/gateway-status.yml`:

- `STRIPE_SANDBOX_SECRET_KEY`
- `MOLLIE_SANDBOX_API_KEY`
- `GOCARDLESS_SANDBOX_ACCESS_TOKEN`

None of these secrets exist yet — until they're added in the repo's GitHub
Settings, the weekly job runs cleanly and simply skips every gateway (no
failure). Add them whenever real sandbox accounts exist for each.

## The public page

`/gateway-status` (linked from the homepage) shows name, regions, SDK
version, last-updated date, sandbox test status + date, and live-test date
for every row — defaulting to Asia-covering gateways first, reflecting the
project's current regional priority. `sandbox_last_error` (the raw exception
text from a failed sandbox check) is stored for maintainer diagnostics but
deliberately never rendered on the public page, to avoid leaking internal
implementation details on an unauthenticated route.

This isn't a static HTML table — it uses the same `Yiisoft\Yii\DataView\GridView\GridView`
grid mechanics as the app's internal list pages (`GatewayStatusListWidget`,
modeled closely on the smaller `App\User\Widget\UsersListWidget` rather than
the much larger `InvsListWidget`, since a public ~8-15-row reference table
doesn't need bulk-action toolbars, CSV export, or group-by): sortable column
headers (Gateway, SDK Version, Last Updated, Sandbox Tested, Live Tested),
real pagination widget wiring, and a region filter (`<select>` + GET submit —
deliberately a plain form rather than `yii-dataview`'s built-in
`DropdownFilter`, since that component has a documented CSP history in this
project — see `docs/YII_DATAVIEW_DROPDOWNFILTER_UPSTREAM_FIX.md`). The table
also gets this project's existing mobile-stacking treatment for free
(`docs/BOOTSTRAP5_TABLE_MOBILE_STACKING.md`'s global `@media (max-width:
767px)` CSS) via `data-label` attributes on each column.

Initial `regions` values were fact-checked against each gateway's own
published country/currency support docs (not guessed) — see the `notes`
field on each row in `gateways.json` for the source and check date.

## Robokassa — first Asia-priority gateway

Asia was chosen as the priority region to build out first. **Robokassa**
was chosen as the first concrete Asia-region gateway to integrate — its real
footprint (confirmed via its own docs) is Russia/Belarus plus CIS Central
Asian countries (Kazakhstan, Kyrgyzstan, Tajikistan, Turkmenistan,
Uzbekistan) and Baltic/other European countries, so `regions: ["asia",
"europe"]` is factually defensible.

Built as a **direct HTTP integration against Robokassa's own API** — no
third-party SDK package. This was a deliberate choice: this codebase already
carries real risk from small, thinly-maintained third-party dependencies
(this session's own `fast-uri`/`brace-expansion` CVE fixes are a concrete
recent example), and Robokassa's official `robokassa/sdk-php` package is
small (12 GitHub stars) with sparse documentation. Robokassa's protocol is
simple enough not to need a wrapper: HTTP requests via `guzzlehttp/guzzle`
(already a dependency) plus this app's own request signing.

Payment initiation uses Robokassa's modern **JWT-based invoice API** rather
than the legacy MD5 query-string redirect scheme, per an explicit
instruction to prioritize current technology over the older integration
style.

Every endpoint, field, and signature formula this integration relies on is
ground-truthed against Robokassa's own official OpenAPI specification
(`https://docs.robokassa.ru/openapi/robokassa.yaml`) — the `createInvoice`
operation for payment initiation, `getOperationState` (OpStateExt) for
payment-status verification, and the `paymentResult` webhook for the
inbound Result URL callback (`SignatureValue` formula
`OutSum:InvId:Пароль#2:Shp_*`, Shp_ params sorted alphabetically). That same
spec explicitly documents both `createInvoice` and `getOperationState` as
production-mode-only (`x-robokassa-environment: testSupported: false`) —
Robokassa's `IsTest` flag only applies to the legacy `Merchant/Index.aspx`
redirect scheme, which this integration deliberately doesn't use. This
confirms what was independently found by checking Robokassa's own site
directly: there is no sandbox environment at all for the API surface used
here, only one production credential set (`login`/`password1`/`password2`).
See `RobokassaPaymentService` and `RobokassaSignatureService` for the
current implementation. `sandbox_status` for this row stays `untested`
since verification can only happen against a real Robokassa merchant
account's live credentials, never a free sandbox.

**Refunds**: the same OpenAPI spec documents a real Refund API
(`RefundService/Refund/Create` + `Refund/GetState`), correcting an earlier
assumption in this integration that Robokassa had none. It's wired up in
`refund()`, with three real constraints the spec makes explicit: (1) it
needs its own separate credential, Password #3, only issued by Robokassa
support once the Refund API is specifically enabled for the merchant
account — most merchants won't have this configured, so `refund()` reports
a clear not-configured message rather than a failed HTTP call when it's
blank; (2) the API is keyed by `OpKey` (the completed operation's own key),
not `InvId` — resolved via an OpStateExt lookup (`Info.OpKey`) before the
refund request itself is signed and sent; (3) unlike CreateInvoice, the
Refund API's own spec documents real 400/401 HTTP statuses with a useful
JSON body on failure, and Robokassa's own spec authors note a real
successful refund wasn't exercised during their own spec verification — so
`success: true` here is only confirmation the refund *request* was
accepted, not that money has moved; `Refund/GetState` exists to poll final
status but isn't wired up yet. Only full refunds are requested (no
`RefundSum`), matching how this app always calls `refund()` with the whole
original payment amount.

## Verification

Full-project `vendor/bin/psalm --no-cache` — no errors. Full Testo suite —
all passing (same 3 pre-existing, unrelated `AmazonPayPaymentServiceTest`
RSA-key environment failures as every other session this month). Full
`vendor/bin/phpunit` — 3,877/3,877 passing, confirming the second Cycle
database didn't regress the MySQL-backed schema. `/gateway-status` and the
refreshed homepage both confirmed rendering correctly against a live local
request.
