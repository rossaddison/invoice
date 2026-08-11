# PayPal Gateway — August 2026

## Summary

Added **PayPal** — this app's broadest-reach gateway, operating in 200+
markets across every populated continent. Built against the **Orders v2
REST API**'s redirect flow (`POST /v2/checkout/orders`, hosted "approve"
page, `POST /v2/checkout/orders/{id}/capture`), not PayPal's JS Checkout
SDK, avoiding any client-side JS integration this app's other gateways
don't need.

## Ground-truthing

PayPal's official PHP SDK, `paypal/paypal-server-sdk`
(`github.com/paypal/PayPal-PHP-Server-SDK`), is genuinely first-party and
actively maintained (pushed 2026-06-05, not archived) — its two
predecessors, `paypal/PayPal-PHP-SDK` and `paypal/Checkout-PHP-SDK`, are
both archived/deprecated. It was deliberately **not installed** as a
composer dependency, for the same reason as Razorpay: its HTTP layer is
APIMatic-generated code built on `apimatic/unirest-php`, a different HTTP
client from the Guzzle client every other gateway in this app is built and
tested against, with no mockable test double. It was read directly from
GitHub for research purposes only, to ground-truth every URL/field/formula.

Confirmed directly from the SDK source
(`Authentication/ClientCredentialsAuthManager.php`,
`Controllers/OAuthAuthorizationController.php`,
`Controllers/OrdersController.php`, `Controllers/PaymentsController.php`):

- OAuth2 client-credentials token flow: `POST /v1/oauth2/token`,
  `Authorization: Basic base64(clientId:clientSecret)`, form body
  `grant_type=client_credentials`. A fresh token is fetched for **every**
  operation rather than cached, since this app's services are stateless
  per-request objects with nowhere natural to persist a ~9-hour-lived token.
- `POST /v2/checkout/orders`, `POST /v2/checkout/orders/{id}/capture`,
  `GET /v2/payments/captures/{id}`, `POST
  /v2/payments/captures/{id}/refund`.

Confirmed directly against PayPal's own current developer docs (reachable
this session, unlike Paystack's 403s):

- Base URLs `https://api-m.paypal.com` (live) /
  `https://api-m.sandbox.paypal.com` (sandbox) — **unlike every other
  gateway added this session**, PayPal's sandbox setting really is a
  different base URL, not just a different credential against the same one.
- `POST /v1/notifications/verify-webhook-signature`'s request shape
  (`transmission_id`, `transmission_time`, `cert_url`, `auth_algo`,
  `transmission_sig`, `webhook_id`, `webhook_event`).
- The `PAYMENT.CAPTURE.COMPLETED` webhook event name.
- The capture resource's `invoice_id` field — this is what carries this
  app's own invoice url_key through to the webhook, set as
  `purchase_units[].invoice_id` at order creation.

**Not independently confirmed**: the exact `verification_status: "SUCCESS"`
response field/value from `verify-webhook-signature` — the fetched docs
page showed the request shape but not a sample response. Based on
well-established, consistently-documented general public knowledge instead,
flagged explicitly in `PaypalPaymentService`'s own docblock.

## Architecture

Mirrors Paystack/Razorpay/Robokassa/YooKassa in shape, with one genuine
structural difference: **`paypalComplete()` is not purely read-only**.
PayPal requires a server-to-server "capture" call after the customer
approves on its hosted page — no money moves automatically, unlike a
classic hosted-checkout redirect. This is a required side effect of
finalizing the transaction (the capture response comes from PayPal's own
API using this app's own credentials, so it's authoritative for that
specific call), not a "trust the client redirect" shortcut. Marking the
invoice paid in this app's own database is still left entirely to
`PaypalWebhookHandler`, matching every other gateway's belt-and-braces
convention.

PayPal's webhook verification is also architecturally unique: instead of a
local HMAC computation, `PaypalPaymentService::verifyWebhookSignature()`
calls PayPal's own `verify-webhook-signature` API — PayPal validates its
per-message RSA signature against a PayPal-hosted certificate server-side,
so this app never implements that cryptography itself. Even after that
API-verified check, `PaypalWebhookHandler` still re-confirms via an
authenticated `GET /v2/payments/captures/{id}` before marking an invoice
paid, the same belt-and-braces pattern used everywhere else.

A customer who cancels on PayPal's page is redirected back to the same
`paypalComplete()` route (reused as both `return_url` and `cancel_url`) —
no `PayerID` query param is present in that case, so the capture attempt is
skipped entirely and the ordinary unpaid/processing message shows instead.

Settings: `clientId` + `clientSecret` + `webhookId` (from the webhook's own
configuration page in the PayPal Developer Dashboard, distinct from the API
credentials) + `sandbox` (a **real** code branch here, unlike every other
gateway this session).

## Regions

North America, South America, Europe, Asia, Oceania, Africa —
`resources/gateway-status/gateways.json`. PayPal's 200+ market reach is
general public knowledge, not exhaustively re-verified per-country this
session; exact send/receive capability varies by country, especially in
parts of Africa.

## ✅ Live-tested against a real account (August 2026)

The assumption this integration originally shipped under — that a
registered company was required to create even a test PayPal Sandbox
business account, the same practical barrier hit with YooKassa,
Paystack, and Razorpay — turned out to be wrong. A personal PayPal
account was sufficient to create a Sandbox Business + Personal buyer
pair via the Developer Dashboard. A real, live, end-to-end sandbox test
(order creation → hosted approval → capture → webhook signature
verification → invoice correctly flipped to paid) passed on a
first-attempt, no-resend basis. `sandbox_status` on the
`/gateway-status` page is `pass`.

That same live-testing pass found and fixed three real production bugs,
none specific to this integration's own logic — see
`docs/PAYPAL_WEBHOOK_ID_DECODE_BUG_AUGUST_2026.md`,
`docs/MYSQL_CONNECTION_CHARSET_BUG_AUGUST_2026.md`, and
`docs/PAYMENT_RECORD_CHANNEL_EMOJI_CHARSET_FIX_AUGUST_2026.md`.

## Verification

- Full-project Psalm (`vendor/bin/psalm --no-cache`): **no errors found**.
- Full Testo suite: all tests passing, including 18 new PayPal tests
  (`PaypalPaymentServiceTest`).
- Full PHPUnit suite (3,877 tests): all passing, after updating the legacy
  `PaymentRefundControllerTest` to pass the new `PaypalPaymentService`
  constructor argument.
- Live-curled the new routes against the running local site
  (`http://invoice.myhost`): `paypalWebhook` returns a clean `400` for an
  unsigned request (this route always makes at least one real HTTP call to
  PayPal's own OAuth endpoint before rejecting — architecturally different
  from every other gateway's purely-local signature check, but not a bug);
  `paypalInForm` returns a clean `404` for a nonexistent invoice url_key.
