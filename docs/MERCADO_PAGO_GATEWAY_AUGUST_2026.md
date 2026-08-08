# Mercado Pago Gateway — August 2026

## Summary

Added **Mercado Pago** as this app's first South-America-priority payment
gateway, built against its **Checkout Pro / Preferences API** — a hosted
checkout page matching this app's existing redirect-based pattern
(Robokassa/YooKassa/Paystack/Razorpay/Mollie/GoCardless), the same shape
Razorpay/Square/PayPal already use.

Mercado Pago is the dominant checkout provider across Argentina, Brazil,
Chile, Colombia, Mexico, Peru, and Uruguay — tied to Mercado Livre, the
region's largest online marketplace. Chosen over the two other real
contenders researched (PagSeguro/PagBank — Brazil-only; EBANX — a
cross-border facilitator worth a second South-America gateway later)
specifically because its sandbox/test credentials sit behind a regular
Mercado Pago account, not a registered company — the same wall that's
blocked Paystack/Razorpay/YooKassa/PayPal Sandbox from ever being
live-tested in this project.

## Ground-truthing

Mercado Pago's official PHP SDK, `mercadopago/sdk-php`
(`github.com/mercadopago/sdk-php`), is genuinely first-party and actively
maintained, requiring PHP 8.2+. It was deliberately **not installed** as a
composer dependency, though: its HTTP transport is a bespoke `CurlRequest`
class (`Net/CurlRequest.php`), not the Guzzle client every other gateway in
this app is built and tested against, and it ships no mockable test double
for that transport. Rather than add a second HTTP-client dependency for one
gateway, this integration is built as direct Guzzle HTTP calls instead,
with every URL/field/formula still ground-truthed directly against that
SDK's real executable source — read via `gh api`, not assumed.

Confirmed directly from the SDK source (`Client/MercadoPagoClient.php`,
`Client/Preference/PreferenceClient.php`, `Client/Payment/PaymentClient.php`,
`Client/Payment/PaymentRefundClient.php`, `Resources/Preference.php`,
`Resources/Payment.php`):

- Base URL `https://api.mercadopago.com` (`MercadoPagoConfig::$BASE_URL`).
- Auth: `Authorization: Bearer {access_token}` — a **single** credential,
  unlike Razorpay's key id + secret pair.
- `POST /checkout/preferences` returns `id`, `init_point` (live checkout
  URL), and `sandbox_init_point` (test checkout URL) — which one this app
  redirects to is controlled by the gateway's own `sandbox` setting, same
  convention as Adyen/Razorpay.
- `GET /v1/payments/{id}` returns `status` (`"approved"` on success) and
  `external_reference`, echoing back whatever this app set on the
  preference at checkout-creation time.
- `POST /v1/payments/{id}/refunds` with `{"amount": <float>}` in the body.
- Amount is a **plain decimal float** throughout
  (`Preference\Item::$unit_price`, `Payment::$transaction_amount`, the
  refund body) — unlike Stripe/Razorpay/Square, Mercado Pago does **not**
  use integer subunits (cents). Matches the plain-float convention already
  used for Mollie/Robokassa/YooKassa.

The webhook signature formula was ground-truthed directly against the
SDK's own dedicated `Webhook\WebhookSignatureValidator` class (not
reused — this app's `MercadoPagoSignatureService` reimplements the same
logic without taking the SDK as a dependency):

- `x-signature` header: comma-separated `key=value` pairs, e.g.
  `"ts=1704908010,v1=<hmac_hex>"`.
- Manifest: `"id:{dataId};request-id:{requestId};ts:{ts};"`, with the
  `id:`/`request-id:` segments **omitted entirely** (not left blank) when
  `data.id`/`x-request-id` are absent.
- HMAC-SHA256 of that manifest using a separate webhook secret (configured
  in Mercado Pago's dashboard under "Tus Integraciones", not the API
  access token), compared via `hash_equals()`.
- `data.id` is lowercased before hashing — per the SDK class's own
  parameter docblock ("Lowercased before HMAC"); its actual `normalize()`
  helper only trims, so this is a caller responsibility the SDK documents
  but doesn't enforce itself. Done explicitly in this app's own
  implementation instead.

The webhook notification body shape
(`{id, type, action, data: {id}}`, `data.id` also present as a query-string
parameter) and the required HTTP 200/201 response within 22 seconds were
additionally confirmed directly against Mercado Pago's own current
developer docs.

## Architecture

Mirrors Razorpay closely, with one structural difference: **Mercado Pago's
webhook notification body carries no invoice reference at all** — just
`type: "payment"` and `data.id`. Razorpay/Stripe both embed the reference
directly in their webhook payloads; Mercado Pago doesn't, so this app
always makes one authenticated `GET /v1/payments/{id}` call to get both
the `status` *and* the `external_reference` together
(`MercadoPagoPaymentService::fetchPaymentDetails()`) — kept separate from
the shared `PaymentGatewayInterface::verifyPayment()` contract (which
`fetchPaymentDetails()` is used to implement) rather than extending the
cross-gateway `PaymentVerificationResult` value type just for this one
gateway's shape.

Another difference worth noting: Mercado Pago lets the webhook
**destination** be set programmatically per-checkout
(`Preference::$notification_url`), not only via a one-time dashboard
config step the way every other gateway here works. `createPayment()`
takes `$notificationUrl` as an explicit parameter — computed by
`MercadoPagoPaymentController` via `UrlGenerator`, the same way
`$callbackUrl` already is — rather than a manually-entered Setting
duplicating a value this app already knows.

Settings: a single `accessToken` (unlike Razorpay's key id + secret pair)
plus a separate `webhookSecret` (same pattern as Stripe/Adyen/GoCardless/
Razorpay's own webhook secret field) and a `sandbox` checkbox controlling
which of `init_point`/`sandbox_init_point` gets used.

### A real bug caught before shipping

`MercadoPagoWebhookHandler` originally set the Merchant audit record's
`driver` field to `'Mercado Pago'` (a human-readable display string, with
a space) — copied from how the gateway is labelled in flash messages.
That's wrong: `PaymentRefundController`'s refund dropdown
(`resources/views/invoice/payment/index.php`) passes the *literal*
`SettingPaymentTrait::activePaymentGateways()` array key
(`'Mercado_Pago'`, underscore) as the route's `{gateway}` argument, and
`MerchantRepository::repoMerchantLatestSuccessfulByInvIdAndDriver()` does
an **exact-string** `WHERE driver = ...` match against whatever gets
stored at webhook time — no case/format normalization anywhere in that
path. A mismatch here would have silently broken every refund for this
gateway (`resolveProviderReference()` finds nothing, refund fails with "no
provider reference found") without ever throwing a visible error. Caught
by cross-checking against the existing `Amazon_Pay` precedent
(`PaymentInformationController.php`'s `'gateway' => 'Amazon_Pay'`) before
shipping, not discovered via a failing test — fixed to `'Mercado_Pago'`
with an explanatory comment at the call site so it doesn't regress.

## Regions

South America (Argentina, Brazil, Chile, Colombia, Peru, Uruguay) plus
North America (Mexico only — not overclaimed further; no Central American
or Caribbean coverage confirmed) — `resources/gateway-status/gateways.json`.
`SiteController::gatewayStatus()`'s `region_priority` sort was **not**
changed to also prioritize South America the way Asia currently is — left
as a deliberate non-change this pass, easy to revisit if wanted.

## ⚠️ Untested against a real account

Mercado Pago's sandbox looking more accessible than Paystack/Razorpay/
YooKassa's (test credentials behind a regular account, not a registered
company) is based on Mercado Pago's own developer documentation, **not yet
independently confirmed against an actual sandbox sign-up this session**.
This integration is verified only against Testo's mocked HTTP responses
and the SDK's ground-truthed source, never against a real Mercado Pago
account. `sandbox_status` on the `/gateway-status` page stays untested
until `MERCADOPAGO_SANDBOX_ACCESS_TOKEN` is actually configured as a
GitHub repo secret and the weekly workflow runs — see
`docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md`.

## Verification

- Full-project Psalm (`vendor/bin/psalm --no-cache`): **no errors found**.
- Full Testo suite: 802/802 passing, including 26 new Mercado Pago tests
  (`MercadoPagoSignatureServiceTest` — 10 cases covering the manifest
  formula's edge cases (lowercasing, omitted segments, multiple signature
  versions); `MercadoPagoPaymentServiceTest` — 16 cases against a mocked
  Guzzle handler).
- Full PHPUnit suite (3,824 tests, zero notices): all passing, after
  updating the legacy `PaymentRefundControllerTest` to pass the new
  `MercadoPagoPaymentService` constructor argument.
- `Functional SiteControllerCest` (25/25) confirms the new
  `gateway-status.sqlite` row didn't disturb the public page.
- Live-curled the new routes against the running local site
  (`http://invoice.myhost`): `mercadoPagoWebhook` returns a clean `400`
  for an unsigned request (not `500`); `/gateway-status?region=south
  america` shows the new Mercado Pago row after `php yii
  gateway-status/rebuild` synced `gateways.json` into the SQLite
  projection.
