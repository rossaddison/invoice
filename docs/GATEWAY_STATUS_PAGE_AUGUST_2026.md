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
| `name`, `regions`, `notes`, `live_tested_at`, `sandbox_env_var`, `sandbox_expiry_date` | Human only — never touched by either command |

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
- `php yii gateway-status/check-sandboxes` — for each gateway with
  `sandbox_env_var` set in `gateways.json`, reads its named environment
  variable(s); if any one is unset, skips that gateway entirely without
  failing (this is what makes rollout incremental per gateway/region). If
  all are set, makes one confirmed side-effect-free sandbox API call and
  records the result.

`sandbox_env_var` is normally a single string (one API key/access token),
but can be a JSON array when a gateway's safe check genuinely needs more
than one value — currently just **Adyen**, whose `paymentMethods()` call
requires both an API key and a merchant account name
(`GatewayStatusRow::$sandboxEnvVars` is always a `list<string>` internally;
`toArray()` only serializes it as a JSON array when there's more than one,
keeping every single-value gateway's JSON unchanged). Values are read in
the order gateways.json lists the env var names and handed to
`checkGateway()`'s per-gateway case in that same order.

**Stripe** (`balance->retrieve()`), **Mollie** (`methods->allEnabled()`),
**GoCardless** (`creditors()->list()`), **Square** (`GET /v2/locations`),
**Adyen** (`POST /paymentMethods` — a genuine read despite the verb, "Get a
list of available payment methods" per the vendored SDK's own docblock),
**Mercado Pago** (`GET /v1/payment_methods`), and **Braintree**
(`merchantAccount()->find($merchantId)`, a plain HTTP GET — confirmed
directly against the vendored SDK source, 2026-08-16, once a real Braintree
sandbox account existed to verify it against) have a confirmed, genuinely
read-only sandbox call wired up — every one verified against this app's own
vendored SDK source (or, for Square, its non-installed-but-read-for-ground-truthing
source) before wiring it in. Amazon Pay still ships with
`sandbox_env_var: null` until a safe no-side-effect call is confirmed for it
(a client-token fetch isn't actually read-only). Open Banking and BACS
aren't classic gateway-SDK pings and stay `sandbox_env_var: null`
permanently.

## GitHub Actions secrets

Naming convention: `{GATEWAY}_SANDBOX_{CREDENTIAL}`, deliberately separate
from this app's own production credentials (which live encrypted in the
`Setting` table, decrypted at runtime via `Cryptor` — see
`docs/PAYMENT_GATEWAY_LIVE_TESTING_JULY_2026.md`). Currently wired into
`.github/workflows/gateway-status.yml`:

- `STRIPE_SANDBOX_SECRET_KEY`
- `MOLLIE_SANDBOX_API_KEY`
- `GOCARDLESS_SANDBOX_ACCESS_TOKEN`
- `SQUARE_SANDBOX_ACCESS_TOKEN`
- `ADYEN_SANDBOX_API_KEY`
- `ADYEN_SANDBOX_MERCHANT_ACCOUNT`

All six are configured as of 2026-08-08 — the weekly job pings every one of
these five gateways' real sandbox APIs and gets `pass` (see
`docs/GATEWAY_STATUS_CI_ENV_FIX_AUGUST_2026.md` for the CI plumbing that
took three fixes to get there). A gateway with no secret configured yet
simply skips (no failure) — deliberately not something Claude sets
directly: these are real sandbox credentials, and shouldn't flow through a
conversation transcript or tool-call history even for a sandbox account —
add them yourself via GitHub → Settings → Secrets and variables → Actions →
New repository secret, whenever a real sandbox account exists for each.

Two more, unrelated to any specific gateway, added the same way — see
"Sandbox credential expiry + Telegram alert" below:

- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`

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

## YooKassa — second Russia/CIS-market gateway, with a real sandbox

**YooKassa** (formerly Yandex.Checkout/Yandex.Kassa) is this app's second
Russia/CIS-region gateway, added alongside Robokassa. Built the same
way — direct HTTP, no third-party SDK/composer package — on this project's
general small-third-party-package caution, independent of how well the
upstream SDK is maintained.

**Two sources for YooKassa's official PHP SDK exist, at very different
versions**: `github.com/yoomoney/yookassa-sdk-php` is a stale, effectively
archived mirror frozen around v2.3.0 (~2022); the actual
actively-maintained source is YooMoney's own Bitbucket instance,
`git.yoomoney.ru/projects/SDK/repos/yookassa-sdk-php`, at v3.14.0 as of
June 2026 per its CHANGELOG.md. Everything `YookassaPaymentService` relies
on was ground-truthed first against the GitHub mirror, then specifically
re-checked against the current Bitbucket source for four years of possible
drift — confirmed identical on both: base URL
`https://api.yookassa.ru/v3`; HTTP Basic Auth
(`Authorization: Basic base64(shopId:secretKey)`); the required
`Idempotence-Key` header (a v4 UUID here, via `ramsey/uuid`, already a
dependency); the `POST /payments`, `GET /payments/{id}`, and `POST /refunds`
paths; the `pending`/`waiting_for_capture`/`succeeded`/`canceled` payment
status enum; the redirect confirmation shape (request
`confirmation: {type: "redirect", return_url}`, response
`confirmation.confirmation_url`); and the HTTP error envelope `refund()`
reads on failure (`{description, code, parameter, retry_after, type}`) —
unchanged even though v3.14.0 refactored that parsing through an
intermediate `Error` model rather than reading the decoded JSON directly.

**YooKassa's API itself has a genuine sandbox**: a free test shop with its
own shopId/secretKey, hitting this exact same production base URL. In
practice, though, signing up (2026-08-04) requires a TIN (Tax
Identification Number — a registered legal entity) even to create a shop,
so an individual cannot obtain test credentials at all — the same practical
KYC barrier hit with Robokassa, just for a different underlying reason:
Robokassa has no sandbox API at all, while YooKassa has one, but account
creation itself is gated behind a TIN. Settings only need one
shopId/secretKey pair (whichever the merchant currently has pasted in);
`sandbox` is informational only, the same pattern already established for
Mollie's single `testOrLiveApiKey` field, not a code branch.

**Webhook authenticity is architecturally different from every other
gateway in this app**: YooKassa's notifications carry no HMAC/signature at
all — ground-truthed via the SDK's `SecurityHelper` class, which documents
an IP allowlist (exact CIDR ranges copied verbatim into
`YookassaWebhookIpVerifier`, re-confirmed identical against the current
v3.14.0 source too) as the only mechanism YooKassa itself provides — no
signing added in four years of active development.
Because IP-allowlisting alone isn't tamper-proof (a reverse proxy can make
`REMOTE_ADDR` unreliable), `YookassaWebhookHandler` treats a passing IP
check as only a fast pre-filter and always re-confirms via an authenticated
`GET /payments/{id}` before ever marking an invoice paid — never trusting
the notification body's own `status` field directly.

Refunds are wired up too (`POST /refunds`, `payment_id`/`amount` body);
`sandbox_status` stays `untested` in `/gateway-status` — no test-shop
account is currently available to verify against. Unlike Robokassa, `sandbox_env_var`
being left `null` for now is a scope decision, not a hard limitation: this
app's `CheckGatewaySandboxesCommand` currently assumes one secret string per
gateway, and YooKassa needs a shopId+secretKey pair, so wiring it into the
weekly CI sandbox-check job needs a small credential-schema decision first.

## Customer-facing checkout flow (Robokassa + YooKassa)

Both gateways initially shipped with only the server-to-server plumbing
(webhook, refund, verify) — the guest-facing "in-form" step that actually
lets a customer pay was a deliberate follow-up, matching the other
gateways' incremental rollout. That step is now built for both.

Unlike Stripe/Mollie/Braintree (which render a local card-entry form) or
Adyen (an embedded drop-in), Robokassa and YooKassa are architecturally
closer to **GoCardless**: both gateways host their own complete payment
page, so the "in-form" step is just a 302 redirect straight to a URL the
gateway hands back, with no local view to render at all —
`RobokassaPaymentController`/`YookassaPaymentController` each got a thin
`{gateway}InForm()`/`{gateway}Complete()` pair mirroring
`GoCardlessPaymentController`'s existing shape exactly.

- **`{gateway}InForm()`**: loads the invoice from `url_key`, confirms the
  gateway is configured and the balance is still positive, then asks the
  service to create the payment/invoice and redirects (302) to the URL it
  returns. Robokassa's `createPaymentUrl()` gained an optional
  `$successUrl` parameter, sent as the spec's `SuccessUrl2Data: {Url,
  Method: "GET"}` (a `RedirectData` object, confirmed via the OpenAPI
  spec) so Robokassa's hosted page sends the browser back afterward;
  YooKassa's `createPayment()` already took a `$returnUrl` for the
  same purpose.
- **`{gateway}Complete()`**: deliberately **read-only**, the same pattern
  already used by `PaymentInformationController::stripeComplete()` — it
  re-reads the invoice's current balance to decide which message to show,
  but never writes payment state itself. Both gateways confirm payment
  asynchronously (Robokassa via the Result URL webhook; YooKassa via its
  own webhook, only after an authenticated re-confirmation given it has no
  signature) — a customer's browser landing on this page proves nothing
  about payment success on its own, so treating it as authoritative would
  be a real bug, not just an inconsistency.

Wired into `PaymentInformationController::pciCompliantGatewayInForms()`'s
dispatch `match` alongside the existing Adyen/GoCardless redirect cases,
and into the two dedicated controllers' constructors (both needed the same
`Flash` injection GoCardless's controller already has, missed on the first
attempt and caught by Psalm's `UndefinedThisPropertyFetch`). No new
translation strings were needed — `already.paid`,
`online.payment.payment.processing`/`successful`, `payment`, and
`complete` already existed and cover every message state these two flows
need.

## Adyen sandbox check — first multi-secret gateway (August 2026)

`CheckGatewaySandboxesCommand` originally assumed one secret per gateway —
exactly the limitation this doc's own YooKassa section flagged as
"deliberately deferred, not an oversight." Adyen forced the decision:
its only genuinely side-effect-free sandbox call, `paymentMethods()`
(`POST /paymentMethods`, "Get a list of available payment methods" per the
vendored SDK's own docblock — a real read despite the POST verb), requires
`merchantAccount` as a hard-required field on the request
(`Adyen\Model\Checkout\PaymentMethodsRequest` throws if it's null) — an API
key alone isn't enough, unlike every other gateway checked here.

`GatewayStatusRow::$sandboxEnvVar` (a nullable string) became
`$sandboxEnvVars` (a `list<string>`), with `fromArray()`/`toArray()`
staying backward-compatible with plain-string JSON for every gateway that
only needs one secret — only Adyen's entry actually serializes as a JSON
array now. `CheckGatewaySandboxesCommand`'s loop reads every named env var
for a row (skipping — not failing — the whole gateway if any one is
unset) and hands the resolved list to `checkGateway()`, indexed in the
same order gateways.json lists the env var names.

This unblocks YooKassa's own multi-secret case (shopId+secretKey) too,
now that the schema decision it was waiting on is made — not done this
pass, since no YooKassa sandbox account exists to verify a check against
(see this doc's own YooKassa section).

Verified: full-project `vendor/bin/psalm --no-cache` — no errors. Full
Testo suite (772/772) and full PHPUnit suite (3,824/3,824) passing, after
updating `GatewayStatusServiceTest`'s fixtures to the renamed
`sandboxEnvVars` constructor parameter. `php yii
gateway-status/check-sandboxes` run locally with no secrets set — every
gateway (including Adyen) skips cleanly with a clear per-gateway message,
confirming the new multi-var loop doesn't fail closed when nothing is
configured.

## Verification

Full-project `vendor/bin/psalm --no-cache` — no errors. Full Testo suite —
all passing (same 3 pre-existing, unrelated `AmazonPayPaymentServiceTest`
RSA-key environment failures as every other session this month). Full
`vendor/bin/phpunit` — 3,877/3,877 passing, confirming the second Cycle
database didn't regress the MySQL-backed schema. `/gateway-status` and the
refreshed homepage both confirmed rendering correctly against a live local
request. The new `robokassaInForm`/`yookassaInForm` routes were also
curl'd directly against the running local site with a nonexistent
`url_key`, confirming a clean `404` (via the language-redirect
middleware's `302` then the controller's own not-found path) rather than a
`500` from a DI-wiring mistake — the same class of check used throughout
this session for routes that can't be fully exercised without real gateway
credentials.

## Pagination summary bug fix + visibility toggle (August 2026)

### The bug

The live page's second row of pagination rendered the literal text
`Page {currentPage} of {totalPages}` instead of real numbers.
`GatewayStatusListWidget` never passed a `TranslatorInterface` to
`GridView::widget()`, so `Yiisoft\Yii\DataView\GridView\BaseListView`
fell back to a translator it builds for itself internally
(`createDefaultTranslator()` — `IdMessageReader` + Intl/SimpleMessageFormatter)
which doesn't correctly substitute the ICU-style `{currentPage}`/`{totalPages}`
placeholders in this app's setup, even with `intl` loaded. Every other
`GridView`-based list widget in this app (`InvsListWidget`,
`ProductsListWidget`, `FamilyListWidget`, `GeneratorListWidget`,
`SalesOrdersListWidget`, `QuotesListWidget`) sidesteps this entirely by
overriding `->summaryTemplate(...)` with a pre-built string —
`GatewayStatusListWidget` (and its stated closest sibling `UsersListWidget`,
which likely has the identical latent bug, not fixed here — out of scope)
were the exceptions.

Fixed by giving `GatewayStatusListWidget`'s constructor a `TranslatorInterface`
and computing the summary itself:

```php
$summary = sprintf(
    $this->translator->translate('gateway.status.page.summary'),
    $this->paginator->getCurrentPage(),
    $this->paginator->getTotalPages(),
);
// ...
->summaryTemplate($summary)
```

with `'gateway.status.page.summary' => 'Page %d of %d'` in
`resources/messages/en/app.php`. `SiteController::gatewayStatus()` now
takes `TranslatorInterface $translator` and passes it through to the
widget. Verified live (`Page 1 of 2` now renders) and with a new permanent
`SiteControllerCest::testGatewayStatusPage()` assertion that
`{currentPage}`/`{totalPages}` never appear in the page source.

### Visibility toggle — `no_front_gateway_status_page`

A new checkbox was added to Settings → Front Page
(`partial_settings_front_page.php`), matching the existing
`no_front_{about,gallery,pricing,...}_page` naming convention exactly.

This one deviates deliberately from what the other ten `no_front_*`
settings actually do. Those only hide a navbar link
(`LayoutViewInjection` → `noFrontPage*` booleans → `NavLink::to(...)` in
`resources/views/layout/templates/soletrader/main.php`) — the underlying
route still returns `200` even with the link hidden. For gateway-status,
the setting also 404s the route itself
(`SiteController::gatewayStatus()` returns
`$webService->getNotFoundResponse()` when the setting is `'1'`), because
this page can expose real (if anonymized) information about which
payment providers are configured, and the intent is to let it be turned
off outright, not just unlinked.

The homepage's "See our payment gateway coverage" link
(`resources/views/site/index.php`) was already gated behind this same
setting. This pass adds the missing second entry point: a `NavLink` in
`main.php`'s guest navbar (icon `bi-credit-card-2-front-fill`, positioned
next to Pricing), following the exact 8-argument `NavLink::to(...)` shape
every sibling link uses — `$isGuest && !$noFrontPageGatewayStatus` for
visibility, mirrored in the trailing argument. `LayoutViewInjection` gained
the matching `noFrontPageGatewayStatus` boolean (both in
`resolveBootstrapSettings()` and the parameters returned to the view).

Verified: full-project `vendor/bin/psalm --no-cache` clean, full Testo
(772/772) and full PHPUnit (3,824/3,824) passing, `Functional
SiteControllerCest` (25/25) passing, and the navbar link confirmed live via
curl against `http://invoice.myhost/` (`<a href="/gateway-status"
class="nav-link active" ...>`). The disabled (`'1'` → `404`) path isn't
covered by an automated test — no existing Functional test infrastructure
toggles a `Setting` row directly, and adding that machinery for one toggle
was judged out of scope for this pass — verified by code review and Psalm
instead, matching the same already-proven pattern the other ten
`no_front_X_page` settings use.

## Sandbox credential expiry + Telegram alert (August 2026)

### Scope, deliberately narrow

Two decisions made explicitly before writing any code, both because the
alternative would have meant touching a different trust boundary than
this feature otherwise lives in:

- **Sandbox credentials only**, not this app's own encrypted production
  gateway credentials (`Setting` table). Those are a separate system with
  a different audience and different risk profile; mixing "your Stripe
  *sandbox* key is about to expire" alerting into the same schema as
  production secrets wasn't worth the coupling.
- **Reuses the existing weekly cron**, not a new, tighter schedule. A
  renewal reminder doesn't need same-day catching, and a second scheduled
  workflow is a second thing to maintain for marginal benefit.

### New field: `sandbox_expiry_date` (renamed from `expiry_date`)

Originally shipped as `expiry_date`, framed around credential/API key
expiry. Renamed the same day, before any real value was ever set on it,
once checking Adyen's own docs turned up a real fact worth designing
around: **API keys generally don't expire on a fixed schedule at all** —
Adyen's stay valid indefinitely until someone manually regenerates one
(the only "expiry" involved is a 24-hour grace period the *old* key gets
after that). What *does* commonly have a real, known expiry is the
**sandbox/trial account** itself — a different thing entirely. So the
field is `sandbox_expiry_date` now, and every docblock/comment reflects
that it's about the account, not the key.

`gateways.json`'s human-curated field set (see the field-ownership table
above) gained one more: `sandbox_expiry_date` (`Y-m-d`, nullable, `null`
on every row by default). Entirely human-entered — set it on a gateway
only when you actually know its sandbox *account* has a real expiry,
same as every other human-only field here. `GatewayStatusRow::isExpired(
string $today): bool` is the one piece of actual logic
(`sandboxExpiryDate !== null && sandboxExpiryDate <= $today`) — kept as a
small, independently unit-tested pure method (`GatewayStatusRowTest`)
rather than buried inline in the console command, since date-comparison
logic is exactly the kind of thing that's cheap to get subtly wrong
(off-by-one on "today", string vs. `DateTime` comparison, etc.).

The `gateway_status` SQLite table gained the matching
`sandbox_expiry_date` column (with its own index, following the same
pattern as `sandbox_tested_at`/`live_tested_at`) via the usual
`BUILD_DATABASE=true` + delete `runtime/schema.php` + reload cycle — see
"Why a second, Cycle-ORM-managed SQLite database" above for why that's a
whole-app-risk operation to do carefully, not just a `gateway_status`
one. Verified directly against the real local SQLite file (`PRAGMA
table_info(gateway_status)`) rather than assumed from the entity
attribute alone. The rename needed a second pass through that same cycle
— Cycle's `SyncTables` generator adds a differently-named column rather
than detecting a true rename, so the original `expiry_date` column (and
its index) was left orphaned after the first sync; confirmed it held
nothing but `NULL` across every row before dropping both by hand
(`DROP INDEX` then `ALTER TABLE ... DROP COLUMN`) rather than leaving
dead schema behind.

Deliberately **not** added to the public `/gateway-status` grid this
pass — the page already shows enough columns, and nothing in the
original ask called for public display of sandbox-expiry metadata. Easy
to add later if wanted; the data's there.

### Telegram notification — from within the app, not the CI workflow

The explicit design constraint: the notification had to be PHP code
making the HTTP call, not a `curl` step written directly into the YAML
workflow. This app already has a full Telegram integration
(`App\Invoice\Helpers\Telegram\TelegramHelper`, a thin wrapper over
`phptg/bot-api`, used for sending clients native Telegram invoices) — that
existing bot is driven entirely by `Setting` table config
(`telegram_token`/`telegram_chat_id`), which is unreachable from a CI job
(no production database; `check-sandboxes` runs against a throwaway,
freshly-schema'd MySQL service container — see
`docs/GATEWAY_STATUS_CI_ENV_FIX_AUGUST_2026.md`). It's also arguably the
wrong bot for this anyway — a client-facing invoicing bot and a
maintainer-facing "your sandbox key expired" alert are different
audiences.

So this reuses `TelegramHelper` (gained one new method, `sendMessage()` —
a thin wrapper over `TelegramBotApi::sendMessage()`, the one call this app
had never wrapped before) but constructs it with credentials from two new,
separate GitHub repo secrets — `TELEGRAM_BOT_TOKEN`/`TELEGRAM_CHAT_ID` —
read via `getenv()` exactly like every sandbox credential in this same
command already is. `CheckGatewaySandboxesCommand::notifyExpiredGateways()`
runs after the sandbox-ping loop, independent of it (a gateway can have a
`sandbox_expiry_date` with no `sandbox_env_var` configured at all, and
still get alerted): filters every row for `isExpired($today)`, and — only
if at least one is — sends one Telegram message listing all of them
(`"⚠️ Gateway sandbox account(s) expired:\n• Stripe: expired 2026-08-01\n..."`).

Design choices worth being explicit about:

- **Stateless, fires every run while expired.** No "already notified"
  tracking — a gateway that stays expired gets re-mentioned every week
  until someone bumps `sandbox_expiry_date`. That's the intended behavior
  (a nag that stops nagging once actually fixed), not a missing feature.
- **Skips silently, not a failure, when either secret is unset** — same
  incremental-rollout philosophy as every sandbox credential above. The
  two secrets aren't in GitHub yet as of this writing; the command runs
  clean either way.
- **A Telegram send failure is caught and logged, never fails the
  command.** An alerting outage shouldn't block the sandbox check,
  rebuild, and commit this same run still needs to do.

### Verification

Full-project `vendor/bin/psalm --no-cache` clean, both before and after
the `expiry_date` → `sandbox_expiry_date` rename. Full Testo suite —
776/776 passing (772 existing + 4 new `GatewayStatusRowTest` cases
covering `isExpired()`'s boundary — no date set, future date, exactly
today, past date — plus one extended assertion in the existing
`GatewayStatusServiceTest::syncToDatabaseUpdatesExistingEntityWhenOneExists`
confirming `sandbox_expiry_date` actually persists through
`syncToDatabase()`). `Functional SiteControllerCest` — 25/25, confirming
the schema change didn't disturb the public page. The `sandbox_expiry_date`
SQLite column, and the clean removal of the orphaned `expiry_date` one
left behind by the rename, were both verified live against the real local
database (`PRAGMA table_info`, not just inferred from the entity
attribute), using the same `BUILD_DATABASE=true` cycle this project
always uses for entity changes, then immediately reverted.

Not verified live end-to-end: the actual Telegram send. `TELEGRAM_BOT_TOKEN`/
`TELEGRAM_CHAT_ID` aren't configured yet, and no `gateways.json` row has a
real `sandbox_expiry_date` set, so `notifyExpiredGateways()`'s
message-sending branch has never actually executed outside its unit
tests. Once both are in place, setting one gateway's
`sandbox_expiry_date` to a past date and running `php yii
gateway-status/check-sandboxes` (or triggering the workflow) is the real
end-to-end check — same "verify live, not just by reading the code"
standard this project holds its other Telegram/webhook integrations to.
