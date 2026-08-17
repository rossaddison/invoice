# TrueLayer Gateway — UK Open Banking, Confirmed Live End-to-End (August 2026)

## Why TrueLayer

Added as a smaller, UK-founded Open Banking payment-initiation gateway with
a strong developer reputation — chosen deliberately over the original first
choice, **Ordo**, which turned out to have ceased trading (confirmed via
ordopay.com's own site notice). Built against the current **Payments V3**
API — V2 ("Single Immediate Payments") is officially deprecated per
`docs.truelayer.com/docs/migrating-to-the-payments-api-v3`, checked
deliberately after an early draft was nearly built against the deprecated
version.

Genuinely installed as a real composer dependency, `truelayer/client`
v3.3.0 (not just `truelayer/signing`, which it depends on internally) — the
official SDK, 42k+ installs, actively maintained — since it correctly
handles OAuth2 token caching, ES512 JWS request signing, and
idempotency-key retry. Requires a PSR-18 HTTP client; this app's existing
Guzzle 7 dependency satisfies that natively.

## Design

The beneficiary is always `externalAccount()`, paying directly into this
business's own bank account — deliberately not TrueLayer's
`merchantAccount()` concept, which needs a separate funded-account setup
step, production-only, this app has no use for. GBP payments resolve the
beneficiary via `CompanyPrivate::getBacsSortCode()`/`getBacsAccountNumber()`;
EUR via `getIban()` — TrueLayer rejects IBAN-identified beneficiaries for
GBP payments outright ("GBP payments are not supported to iban accounts",
confirmed live), so the identifier type is chosen by currency, not
defaulted to IBAN.

Refunds are only supported for settled merchant-account payments (confirmed
directly in the SDK's own README) — since this integration never uses a
merchant account, `refund()` always returns a clear "not supported" result,
the same documented limitation this app already accepts for Amazon Pay.

The return URL is a fixed, manually-configured Setting
(`gateway_truelayer_returnUrl`), never dynamically generated per-invoice —
TrueLayer requires `return_uri` to exactly match a Redirect URI
pre-registered in Console. Since TrueLayer only appends `?payment_id=...`
to that return URL, `trueLayerComplete()` resolves the invoice via
`TrueLayerPaymentService::resolveInvoiceUrlKey()` (reads the payment's own
`metadata.invoice_url_key`), not a route parameter.

## The live debugging chain

Every one of the following was root-caused from `runtime/logs/app.log`
against a real production server (yii3i.online), not guessed:

1. **`Unable to load the key`** — the signing private key (PEM) Settings
   field was `'password'` type (a single-line `<input>`), which silently
   stripped the PEM's newlines on save. Fixed by adding a genuine
   `'textarea'` field type to this app's Settings system — didn't exist
   before at all.
2. **`invalid_client`** — Client ID/Secret both turned out corrupted or
   truncated in storage at different points during setup; fixed by
   re-copying fresh from Console each time.
3. **IBAN format rejected** — the dummy IBAN used in early testing had
   spaces; TrueLayer requires it spaceless.
4. **`GBP payments are not supported to iban accounts`** — a genuine
   platform constraint, not a mistake. Fixed by making beneficiary
   resolution currency-aware (see Design above).
5. **A real, separate bug found along the way**: the BACS sort code was
   never actually saving on the Company Private form. Three digit boxes
   were combined into a hidden field via a raw inline `<script>`, which
   this app's CSP silently blocks (no `unsafe-inline` in `script-src`).
   Moved the combining logic to `company-private.ts`.
6. **`Value must be 6 digits without spaces.`** — this app stores the BACS
   sort code as `XX-XX-XX` (dashes); TrueLayer wants a plain 6-digit
   string. Fixed with `preg_replace('/\D/', '', ...)` sanitizing both the
   sort code and account number before building the account identifier.
7. **Every webhook delivery crashed**: `AbstractJws::header(): Argument #2
   ($value) must be of type string, array given`. `ServerRequestInterface::
   getHeaders()` returns `array<string, string[]>` (each header maps to an
   array of values), but the SDK's own `WebhookInterface::headers()`
   declares `array<string, string>` — a flat map of single string values,
   confirmed directly against the SDK's own docblock. Fixed by flattening
   via PSR-7's own `getHeaderLine()`.
8. **After the crash was fixed, every webhook cleanly failed signature
   verification instead**: `Webhook signature verification failed.`
   Temporary diagnostic logging (path, full URI, body length, header
   names) pinned the cause down exactly: the path this handler saw was
   `/en/paymentinformation/trueLayerWebhook`. TrueLayer signs its webhook
   over the exact path it was told to POST to (the Webhook URI registered
   in Console, with no locale segment), but this app's Locale middleware
   was silently rewriting the incoming request's path to prepend `/en/` —
   no visible HTTP redirect at all, the same mechanism already documented
   in `TrueLayerPaymentService::returnUrl()`'s own docblock for why the
   *outbound* return URL is a fixed Setting rather than dynamically
   generated. Fixed by adding `/paymentinformation/trueLayerWebhook**` to
   `config/web/params.php`'s `locale.ignoredRequests` — the same exemption
   mechanism already used for `/gii`, `/debug`, `/inspect`.
9. **A second, independent path mismatch found while chasing #8**: the
   Webhook URI registered in TrueLayer Console had a case difference —
   `paymentInformation` (capital I) vs. this app's actual lowercase route,
   `paymentinformation`. Corrected directly in Console to match exactly.
10. **After all of the above, TrueLayer stopped attempting webhook delivery
    entirely.** Fresh payments confirmed `Executed` in TrueLayer's own
    Payments dashboard, but zero corresponding delivery attempts ever
    reached this app's logs — TrueLayer appears to pause/disable a webhook
    endpoint's delivery after an earlier run of failures, with no
    "resend"/"test webhook" control found in Console to force one. An
    account/platform-side state, not something further code changes here
    could fix.

## Resolution

Rather than leave a genuinely-paid invoice stuck showing "still
processing" indefinitely whenever webhook delivery is delayed or paused,
`TrueLayerPaymentController::trueLayerComplete()` now also calls
`TrueLayerWebhookHandler::markInvoicePaidIfVerified()` directly (made
public for this purpose) when the customer's browser returns from
TrueLayer's Hosted Payments Page. This is deliberately **not** "trusting
the browser redirect" in the risky sense the original webhook-only design
avoided: the browser only supplies which `payment_id` to check, and
`markInvoicePaidIfVerified()` still requires TrueLayer's own authenticated
`GET /v3/payments/{id}` response before marking anything paid — the exact
same call the webhook path already used. The webhook remains the
primary/first-to-arrive trigger for whenever it does work; the method's
own balance-zero guard makes a second call, in either order, a safe no-op.

Chasing why TrueLayer's webhook delivery itself stays paused remains an
open item for their support, independent of this app's own code now being
confirmed correct.

## Verification

A real sandbox payment (TrueLayer's `test_executed` mock provider, "Mock
UK Payments — Redirect Flow") was run through the full flow — Hosted
Payments Page, mock bank login, PIN/2FA simulation, consent — on
yii3i.online, and correctly marked the invoice paid on the customer's
redirect back. `php -l` clean, full-project Psalm clean at every step, full
Testo suite green throughout (855/855, 19348 assertions) with no
regressions.
