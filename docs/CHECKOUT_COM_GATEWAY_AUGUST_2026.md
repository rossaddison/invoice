# Checkout.com Gateway — August 2026

## Summary

Added **Checkout.com** as this app's 12th online payment gateway. Built
against the **Payment Links API** (`POST /payment-links`, an Order-based
hosted checkout page redirecting to `pay.sandbox.checkout.com/link/{id}`)
via the official `checkout/checkout-sdk-php` package — the same
conceptual role Square/Razorpay/Mercado Pago/PayPal's own hosted-link
products already play in this app.

## Installed as a real dependency — the first "new" gateway to be since Adyen/GoCardless

Every gateway added since Razorpay (Razorpay, Mercado Pago, PayPal,
Square) deliberately kept its official SDK **uninstalled**, read only
for ground-truthing, because each one's HTTP transport genuinely isn't
Guzzle (APIMatic-generated `apimatic/unirest-php` for Razorpay/PayPal/
Square, a bespoke `CurlRequest` class for Mercado Pago) — none of them
ship a mockable test double compatible with how every other gateway in
this app is tested.

Checkout.com breaks that pattern: its own `composer.json` genuinely
requires `guzzlehttp/guzzle ^7.4`, confirmed directly from the SDK's
real source, the same HTTP client this app's other real-dependency
gateways (Stripe, Mollie, GoCardless) are already built and tested
against. So `checkout/checkout-sdk-php` (v5.3.0, released 2026-08-06,
actively maintained) was installed as a genuine composer dependency —
the deciding factor that kept the other four SDK-free cuts the other
way here.

## Ground-truthing

Every endpoint, field, and auth detail was ground-truthed directly
against the SDK's real executable source
(github.com/checkout/checkout-sdk-php, confirmed 2026-08-11):

- **Auth**: `Authorization: Bearer {secretKey}` (`SdkAuthorization.php`)
  — a single static secret key, format `sk_.../sk_sbox_...`, validated
  by the SDK's own regex (`CheckoutStaticKeysSdkBuilder`). `publicKey`
  is genuinely optional (`AbstractStaticKeysCheckoutSdkBuilder::validatePublicKey()`
  returns early on empty) — only needed for client-side tokenization
  this app's hosted-redirect flow never uses, but the Settings field is
  kept for parity with every other gateway's field set and possible
  future use.
- **Base URLs**: `api.sandbox.checkout.com` / `api.checkout.com`
  (`Environment.php`) — genuinely a different base URL per environment,
  like PayPal/Square, not just a different credential.
- **`POST /payment-links`** (`Payments/Links/PaymentLinksClient.php`):
  the created link's redirect URL is `_links.redirect.href` in the
  response — confirmed via Checkout.com's own current API reference,
  since the SDK itself returns a raw untyped array here with no typed
  response model.
- **`GET /payments/{id}`** for verification, **`POST /payments/{id}/refunds`**
  for refunds (`Payments/PaymentsClient.php`).
- **`Cko-Signature` webhook header**: hex-encoded (Base16) HMAC-SHA256
  of the *raw* request body, keyed by the webhook's own signing key (a
  separate credential from the account's API secret key, generated when
  the webhook endpoint is configured in Checkout.com's Hub) — confirmed
  via Checkout.com's own published support documentation
  (support.checkout.com/hc/en-us/articles/29686673313426). Deliberately
  raw-body-first in `CheckoutComWebhookHandler`, matching Checkout.com's
  own explicit warning that a decode-then-re-encode signature
  computation can silently change numeric precision and produce a false
  mismatch — the same class of gotcha already known from this app's
  other JSON-body-signed gateways (PayPal, Adyen, Mercado Pago).
- **Webhook event types**: `payment_approved` (authorization only) then
  `payment_captured` (money actually moved) — confirmed via
  Checkout.com's own webhook event type documentation. Only
  `payment_captured` is trusted, matching every other gateway's
  "captured/settled", not "authorized", threshold.

Not independently re-confirmed via a raw example webhook payload this
session: the exact `status` string casing for a fully captured payment
(`'Captured'`, based on Checkout.com's general documentation
conventions, not a fetched raw JSON example) — flagged in
`CheckoutComPaymentService`'s own docblock, the same way PayPal's own
docblock flags its one unconfirmed field.

## A real SDK-typing gotcha, and the structural fix

The SDK's own fluent builder methods (`staticKeys()`, `secretKey()`,
`environment()`, `publicKey()`, `httpClientBuilder()`, `build()`, and
`CheckoutApi`'s `getPaymentLinksClient()`/`getPaymentsClient()`) declare
**no return types at all** on their method signatures — every one of
them genuinely returns `$this` (or, for `build()`, a real `CheckoutApi`),
confirmed by reading each method's actual source, but Psalm has no way
to know that from the SDK's own untyped code. Chaining them directly
(`CheckoutSdk::builder()->staticKeys()->secretKey(...)->...`) degrades
to `mixed` at every step.

Per this project's own convention (`@psalm-suppress` is never the
answer — find a structural fix), this was resolved with explicit `@var`
annotations at just the two points where Psalm's own inference actually
needed help (`CheckoutSdk::builder()`'s return, and each client getter
via two small private typed accessor methods, `paymentLinksClient()`/
`paymentsClient()`) — everything downstream of those two anchors, Psalm
was then able to infer correctly on its own via the SDK's docblocks,
confirmed by Psalm itself flagging three of the annotations as
`UnnecessaryVarAnnotation` once removed. Full-project Psalm: 100% type
inference, zero errors.

## Architecture

- `CheckoutComPaymentService` (`PaymentGatewayInterface`) —
  `createPaymentLink()`, `verifyPayment()`, `refund()`,
  `webhookSigningKey()`. Accepts an optional injectable Guzzle client
  (test-only — wrapped in the SDK's own `HttpClientBuilderInterface` via
  a tiny anonymous class), the same mockability approach
  `MercadoPagoPaymentServiceTest`/`PaypalPaymentServiceTest` already use
  for their own Guzzle-based tests.
- `CheckoutComSignatureService` — the `Cko-Signature` HMAC verification,
  isolated the same way `PaystackSignatureService`/`RazorpaySignatureService`
  already separate signature logic from the payment service itself.
- `CheckoutComWebhookHandler` — signature check, then the same
  belt-and-braces re-confirmation via `GET /payments/{id}` every other
  gateway's webhook handler performs before ever marking an invoice
  paid. `data.reference` is this app's own invoice `url_key` directly
  (set on the Payment Link at creation time and echoed back unchanged)
  — the same role Adyen's `merchantReference` plays; no separate
  metadata lookup needed, unlike Paystack's `data.metadata.invoiceUrlKey`.
- `CheckoutComPaymentController` — its own dedicated controller, same
  reasoning as every gateway added since Adyen
  (`PaymentInformationController` is already at SonarQube's `php:S1448`
  method-count ceiling). `checkoutComInForm()` redirects straight to the
  hosted Payment Link; `checkoutComComplete()` is read-only, matching
  `SquarePaymentController::squareComplete()` exactly — Checkout.com's
  Payment Links auto-capture server-side, so there is no separate
  capture step for this action to trigger, unlike PayPal.
- Wired into `PaymentInformationController::pciCompliantGatewayInForms()`'s
  dispatch `match`, `PaymentRefundController`'s refund dispatch `match`,
  `SettingPaymentTrait::activePaymentGateways()`/`sandboxUrlArray()`, the
  webhook route's `CsrfExemptMiddleware` allowlist, and
  `resources/gateway-status/gateways.json` — the same checklist every
  prior gateway addition in this app follows.

## Regions

Every populated continent — Checkout.com's own publicly documented
global coverage (general public knowledge, not exhaustively
re-verified per-country this session, matching the same caveat already
applied to PayPal/Stripe's own regions entries).

## Untested against a real account — status

No real Checkout.com sandbox account was created or tested against this
session. Unlike Paystack/Razorpay/YooKassa (confirmed, genuine
company-registration or local-tax-ID walls this project's maintainer
has directly hit), Checkout.com's own test-account signup
(`checkout.com/get-test-account`) has not yet been attempted, so
whether the same class of wall applies here is genuinely unknown, not
assumed — `sandbox_status` and `sandbox_env_var` in `gateways.json` are
deliberately left `null` rather than `'untested'`, since that value in
this file specifically means "confirmed blocked," not "not yet tried."
This integration is verified only against Testo's mocked Guzzle
responses and the SDK's ground-truthed source, never against a real
Checkout.com account.

## Verification

- Full-project Psalm (`vendor/bin/psalm --no-cache`): **no errors
  found**, 100% type inference.
- Full Testo suite: 828/828 passing (810 existing + 18 new —
  `CheckoutComPaymentServiceTest` covers `getDriverKey()`,
  `isConfigured()`, `createPaymentLink()` success/missing-redirect-url/
  API-error, `verifyPayment()` captured/not-captured/API-error, and
  `refund()` success/API-error, all against a real Guzzle `MockHandler`
  exercising the SDK's actual request/response handling;
  `CheckoutComWebhookHandlerTest` covers invalid signature, a
  non-capture event type, the full happy path, already-paid no-op,
  invoice-not-found, and re-check-not-confirmed, using a genuinely
  computed `Cko-Signature` and the same mocked-Guzzle-backed payment
  service).
- Full PHPUnit suite: 3,877/3,877 passing, after adding the new
  constructor argument to the legacy `PaymentRefundControllerTest`'s
  `makeController()` helper.
- `php -l` clean on every new/changed file.
