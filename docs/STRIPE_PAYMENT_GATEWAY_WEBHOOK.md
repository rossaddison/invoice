# Stripe Payment Gateway — Webhook, Interface & Hardening

## Why

`stripeComplete()` marked an invoice paid purely by trusting a client-supplied
`?redirect_status=succeeded` query parameter on the browser's return from
Stripe — the server never called back to Stripe to confirm anything actually
happened. Hitting that URL directly with a forged query string would mark any
invoice paid with no payment having occurred. Alongside that: a dangling
`stripeIncomplete` route pointed at a controller method that didn't exist;
Stripe's JS/CSS loaded on every page in the app instead of just the payment
page; and Stripe had no brand/logo treatment unlike Braintree and Mollie.

## What changed

### Webhook — the core fix

- New `POST /paymentinformation/stripeWebhook` route, registered **outside**
  `RoutePermission::invoiceGroup()` (Stripe's servers have no app session —
  same placement pattern as the existing `telegram/webhook`).
- `StripePaymentService::verifyWebhookSignature()` wraps
  `\Stripe\Webhook::constructEvent()` against a new `webhookSecret` setting
  (`gateway_stripe_webhookSecret`), added to `activePaymentGateways()['Stripe']`.
  `stripe-php` is already pinned `>=19`, which supports this natively.
- `PaymentInformationController::stripeWebhook()` handles
  `payment_intent.succeeded` / `payment_intent.payment_failed`, is idempotent
  (checks `InvAmount::getBalance() === 0.0` before reprocessing), and is the
  **sole writer** of Stripe payment status. The controller action itself is a
  one-line delegator to `Service\StripeWebhookHandler::handle()` — the actual
  signature verification, event resolution, and invoice/payment writes live
  there instead, decomposed into small guard-clause methods
  (`resolveContext()`, `applyEvent()`). This split was needed to keep
  `PaymentInformationController` under SonarQube's per-class method limit
  (php:S1448, 20 methods) and to let the guard-clause chain stay under the
  per-method return-count limit (php:S1142, 3 returns) without cramming
  everything into one long method. Both were caught by SonarCloud CI *after*
  the initial merge, not by local Psalm — Psalm has no equivalent
  complexity/structure metrics, so this class of finding only surfaces once
  CI's SonarCloud scan runs; worth checking for after any commit that adds
  methods to an already-large class, not just relying on `psalm --no-cache`.
- `stripeComplete()` (the browser-redirect landing page) is now **read-only**
  — it re-reads current invoice state and uses `redirect_status` only to pick
  wording, never to write anything.
- **Write ordering matters**: the webhook writes the `payment`/`merchant`
  audit record *before* finalising the invoice's own `status_id`/balance. If
  that insert throws, the invoice is left exactly as it was (balance
  untouched), so the idempotency guard won't later skip a genuine retry. The
  reverse order was tried first, found live, and reverted — see the live
  testing doc below.

### Shared payment-recording, also extracted

The payment/merchant audit-record write (previously
`PaymentInformationController::recordOnlinePaymentsAndMerchant()`, private,
~100 lines) is used by Braintree's and Mollie's completion flows too, not
just Stripe's. It's now `Service\OnlinePaymentRecorderService::record()`, a
standalone class with `PaymentService`/`MerchantService`/`Flash` injected
directly rather than borrowed from the controller's own constructor — this
alone dropped the controller from 21 methods to 20, clearing php:S1448
without needing to touch anything gateway-specific. Braintree's
`renderBraintreePostResponse()` and Mollie's `mollieComplete()` were updated
to call `$this->paymentRecorder->record(...)` in place of the old private
method call; both were re-verified live after the change (see the live
testing doc).

### CSRF exemption for the webhook

`CsrfTokenMiddleware` was applied globally with no per-path exemption
(`config/common/di/router.php` + `config/web/params.php`, registered twice),
which would 422 any POST from Stripe's servers before it ever reached the
signature check. New `App\Middleware\CsrfExemptMiddleware` decorates
`CsrfTokenMiddleware`, skipping validation only for
`/paymentinformation/stripeWebhook`; every other path is unaffected (pure
delegation). `telegram/webhook` and `as4/receive` look like they have the
identical latent gap — not fixed here, flagged as a one-line follow-up given
the decorator already exists.

### `PaymentGatewayInterface`

New `getDriverKey(): string` / `isConfigured(): bool` /
`verifyPayment(string $providerReference): PaymentVerificationResult`
contract, implemented fully for Stripe (webhook-verified) and retrofitted as
thin, behavior-preserving methods onto `BraintreePaymentService` and
`AmazonPayPaymentService` — both classes stay in active use for their
existing functionality regardless, so the interface conformance is additive
with no risk of going stale unnoticed. Nothing dispatches through the
interface itself yet — `pciCompliantGatewayInForms()`'s `match()` is
untouched, matching the agreed scope of "Stripe now, interface for all later."

A `MolliePaymentGatewayAdapter` was initially written the same way (Mollie
has no dedicated service class — its logic still lives directly in the
controller), but a full-project Psalm run (`UnusedClass`) caught that it had
zero consumers anywhere — unlike Braintree/Amazon Pay, there was nothing else
keeping it referenced. Deleted rather than left as dead code; Mollie has no
`PaymentGatewayInterface` conformance for now. Per-file Psalm checks during
development didn't catch this — full-project Psalm can detect unused classes
where per-file/folder analysis explicitly can't (documented in Psalm's own
"cannot detect unused classes... when analyzing individual files" notice) —
worth running before every commit, not just per-file during iteration.

Open Banking (Wonderful/Tink) was **not** given an adapter either: its only
related SDK calls *create* a new payment request each time rather than
looking one up by reference, so a `verifyPayment()` there would be actively
misleading without an actual rewrite — out of scope.

### Appearance

- Stripe's JS/CSS (`StripeVersionTenAsset`) now registers from inside
  `payment_information_stripe_pci.php` instead of unconditionally in
  `layout/invoice.php` / `layout/guest.php` — loads only on the Stripe
  payment page (same pattern already used by `payment_information_amazon_pci.php`
  for `AmazonPayTwoSevenAsset`).
- `checkout.css`'s previously-global `*`, `button`, `form` selectors scoped
  to `#payment-form`, excluding `#submit` specifically (which already has its
  own Bootstrap `.btn-success` styling that these rules were fighting with).
- `PaymentInformationLogoRenderer::stripeLogo()` added, matching the existing
  `braintreeLogo()`/`mollieLogo()` pattern, rendering
  `logo/stripeLogo.php` — reuses the existing local `/img/stripe.png` asset
  (already referenced by `PaymentGatewayButton::stripe()` elsewhere, confirmed
  still in active use in `inv/view.php`, not dead code).

### Dead code removed

- `stripeIncomplete` route and its dangling controller reference.
- `PaymentInformationQueryHelper::getStripePciClientSecret()` — exact
  duplicate of `StripePaymentService::createPaymentIntent()`, never called.

## Local Testing Setup (Stripe CLI)

Stripe's real servers can't reach a local WAMP install (`Require local` in
the vhost config, no public URL), so registering a Dashboard webhook doesn't
work for local dev — the Stripe CLI's `listen` command is the supported way
to forward real Stripe events to `localhost` without exposing it publicly.

1. **Install** — on Windows, via `winget`:
   ```powershell
   winget install --id Stripe.StripeCli -e --accept-package-agreements --accept-source-agreements
   ```
   The package ID is `Stripe.StripeCli` (lowercase `li`) — `Stripe.StripeCLI`
   (all-caps) doesn't exist and fails with "No package found matching input
   criteria." If unsure, `winget search stripe` lists the correct ID.
   `winget` updates `PATH` but the *current* terminal session won't see it —
   close and reopen the terminal before `stripe` resolves as a bare command.

2. **Log in**:
   ```powershell
   stripe login
   ```
   Prints a pairing code and opens (or prompts you to open) a
   `https://dashboard.stripe.com/stripecli/confirm_auth?...` confirmation
   page. This times out ("exceeded max attempts") if the browser
   confirmation isn't completed within roughly a minute or two — just
   `stripe login` again if it does.

3. **Forward events to the local webhook route**, and *keep this terminal
   running* for the entire test session:
   ```powershell
   stripe listen --forward-to http://localhost/en/paymentinformation/stripeWebhook
   ```
   **This is the step most likely to silently go wrong**: running bare
   `stripe listen` (no `--forward-to`) still connects, still prints incoming
   events, and looks exactly like it's working — but it never makes an HTTP
   call to the app at all. Nothing reaches `app.log`, no invoice ever gets
   marked paid, and there's no error to indicate why. If a real payment isn't
   showing up server-side, check the `--forward-to` flag is actually there
   before debugging anything else.

4. The command prints a signing secret on startup:
   ```
   Ready! Your webhook signing secret is whsec_xxxxxxxxxxxxx (^C to quit)
   ```
   Paste that into **Settings → Online Payments → Stripe → Webhook Secret**
   and save. This secret is generated fresh each time `stripe listen` starts
   (not the same value as any Dashboard-registered endpoint would have), so
   it needs re-pasting if the CLI session restarts.

5. **Test it** — with `stripe listen` still running, either pay a real
   invoice through the UI with test card `4242 4242 4242 4242` (any future
   expiry, any CVC), or trigger a synthetic event without touching the UI:
   ```powershell
   stripe trigger payment_intent.succeeded
   ```
   Watch the `stripe listen` terminal for the event being forwarded and the
   HTTP status the app returned (`200` once everything's configured
   correctly; `400` means the signature check rejected it — see the
   troubleshooting notes in
   [Payment Gateway Live Testing](PAYMENT_GATEWAY_LIVE_TESTING_JULY_2026.md)
   for what actually caused a `400`/`500` in practice during this session).

## Verification

- `vendor/bin/psalm --no-cache` clean on every touched/new file.
- New `Tests/Unit/Invoice/PaymentInformation/StripeWebhookSignatureTest.php`
  exercises `\Stripe\Webhook::constructEvent()` directly (valid signature
  accepted, tampered payload rejected, wrong secret rejected) — pure HMAC
  computation, no network I/O. `StripePaymentService` itself isn't
  instantiated in tests: its constructor requires the concrete `final
  SettingRepository` class (not `SettingRepositoryInterface`), which PHPUnit
  cannot generate a test double for — a pre-existing constructor-design
  constraint, unrelated to this change.
- Full live end-to-end verification against a real local Stripe CLI session
  — see [Payment Gateway Live Testing](PAYMENT_GATEWAY_LIVE_TESTING_JULY_2026.md)
  for the bugs that surfaced only under real testing and how they were fixed.
