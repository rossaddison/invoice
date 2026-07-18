# Payment Gateway Live Testing — July 2026

## Why

Static review and unit tests caught real problems in the Stripe webhook
rewrite (see [Stripe Payment Gateway — Webhook, Interface & Hardening](STRIPE_PAYMENT_GATEWAY_WEBHOOK.md)),
but several defects only surfaced once real invoices were paid end-to-end
through each gateway, driven manually via the admin UI (create invoice →
log in as the `Observer`-role user → pay) with server-side verification
(`app.log`, direct DB queries) after each step. None of these were
reproducible from code review alone.

## Stripe — bugs found live

- **Webhook secret stored as plaintext, not encrypted.** `secretKey`/
  `publishableKey` decrypted correctly via `SettingRepository::decode()`
  (confirmed against a known-good `sk_test_...` value); `webhookSecret` did
  not — `Cryptor::decrypt()` on a never-encrypted plaintext value doesn't
  error (AES-256-CTR has no integrity check), it silently returns garbage
  bytes, so every signature check failed with "No signatures found matching
  the expected signature for payload." Root cause in the settings save flow
  was never conclusively identified (the encode-on-save path is generic and
  identical for every password-type field); fixed by re-encrypting and
  writing the correct value directly. Re-saving through the UI after a hard
  refresh did not reproduce it.
- **`payment_method` table missing IDs 1–8.** Every gateway integration
  (Stripe, Braintree, Mollie, Amazon Pay) hardcodes `4` = paid / `5` = failed,
  but this environment's table only had IDs 9–16 (the same 8 rows, shifted —
  looks like the install seed ran twice with the auto-increment counter not
  reset). First real `payment_intent.succeeded` webhook hit a foreign-key
  violation on insert. Fixed by inserting the canonical 1–8 rows from
  `InvoiceInstallTrait::installDefaultPaymentMethods()`, leaving the existing
  9–16 rows (and the 5 payment records already referencing them) untouched.
- **Discovered, not fixed**: the canonical seed order is actually `3` =
  "Payment Succeeded", `4` = "Payment Processing" — every gateway's hardcoded
  `4` for success is off by one against that label. Doesn't block payments
  (invoice `status_id` is separate), but mislabels every successful online
  payment's `payment_method` in reports across all four gateways. Pre-existing,
  cross-cutting, not fixed here.
- **Invoice number recorded as literal `"1"`.** `(null !== $invoice->getNumber()) ?: 'unknown'`
  evaluates the `!==` comparison to a boolean *first* — `true` casts to
  `"1"` — so `PaymentRecordContext::reference` read `"1-payment_intent.succeeded"`
  instead of e.g. `"INV271-payment_intent.succeeded"`. Present in the
  original `stripeComplete()`, re-introduced in the new `stripeWebhook()` by
  copying the same pattern, and found identically in `mollieComplete()`.
  Fixed in all three call sites: `$invoice->getNumber() ?? 'unknown'`.
- **Write-ordering caused a real "paid with no audit record" invoice.** The
  first live webhook for one invoice hit the `payment_method` FK crash above
  *after* the invoice's own `status_id`/balance had already saved — so the
  invoice was left showing paid with no `payment`/`merchant` row. The retry's
  later webhook saw `balance === 0` and silently skipped as a duplicate.
  Fixed by writing the audit record first (see the ordering note in the
  webhook doc); the affected invoice's missing records were backfilled by
  hand to match reality.
- **False "Payment failed" on a genuinely successful payment.** Stripe's
  client-side redirect can report `redirect_status=succeeded` before the
  async webhook has finished confirming it server-side — a timing race, not
  a failure. `stripeCompleteHeading()` only treated `redirect_status ===
  'processing'` as "still working"; anything else not-yet-paid fell through
  to the failure message. Fixed: `succeeded` is now also treated as
  still-processing until the webhook confirms it
  (`PaymentInformationQueryHelper::isStripeStillProcessing()`).
- **"This page will update once confirmed" was a false promise.** The
  completion page (`payment_message.php`) is a fully static render with no
  polling/refresh mechanism. A `<meta http-equiv="refresh">` auto-reload was
  tried and reverted — it tripped a real accessibility lint
  (`Web:MetaRefreshCheck`; WCAG timing-adjustable concern), and this page
  sits outside the app's normal TS-bundle/CSP-nonce pipeline, so doing it
  properly with accessible JS would be more machinery than justified. Fixed
  by correcting the wording instead: "Please check back shortly to confirm."
- **`payment_message.php` vertical centering was inconsistent.** Its
  `display: table` / `table-cell` centering technique never set
  `vertical-align: middle` on the `body` (`table-cell` defaults to
  `baseline`), so the block's position shifted depending on content height
  (e.g. whether a sandbox-dashboard link was present). One-line fix.

## Braintree — bug found live

- **No CSRF token on the card-nonce form**, unlike every other form in the
  codebase (`payment_message.php` embeds one via the globally-injected
  `$csrf` object from `CsrfViewInjection`). Every submission 422'd
  ("Unprocessable Entity") since Braintree is the only one of the four
  gateways that POSTs a card nonce directly back to our own server — Stripe's
  form is intercepted client-side and never natively submits;
  Mollie/Amazon Pay redirect off-site with no form at all. Fixed by adding
  `$csrf->getParameterName()` / `$csrf->getToken()` as a hidden input,
  matching the existing `payment_message.php` pattern.
- Confirmed clean otherwise: synchronous nonce-based flow (no webhook, no
  async race class of bug), correct payment/merchant records, correct
  invoice number in the reference (this code path was never touched by the
  ternary bug above).

## Mollie — no code bugs found

Redirect-based flow (`mollieInForm()` → Mollie-hosted checkout →
`mollieComplete()` re-fetches Mollie's recent payments and matches by
metadata). Test blocker was user error, not a defect — Mollie's card field
does client-side Luhn validation and needs a checksum-valid test number
(`4242 4242 4242 4242` works fine; no Stripe-style "magic" number is
required in Mollie test mode). Verified clean end-to-end after using a valid
card number, including the invoice-number reference fix above.

**Known, not fixed**: `mollieComplete()` finds the matching payment by
scanning `$mollie->payments->page()` (the most recent page only) rather than
fetching by a stored payment ID — a real scalability gap once a sandbox
account accumulates enough payments that the match falls off the first page.

## Amazon Pay — real bug found, plus external config gaps

- **CSP `img-src` was missing `*.payments-amazon.com`**, even though
  `script-src`, `connect-src`, and `frame-src` all correctly included it.
  That's why Amazon's own button graphics rendered as broken images — the
  browser was silently blocking them. Fixed in both `config/web/params.php`
  (deduplicated into a shared `$amazonPayCsp` variable once the same literal
  hit 4 occurrences — flagged by the SonarQube-style S1192 check) and the
  mirrored policy in `public/.htaccess`, which must stay in sync since
  browsers apply the intersection of both headers. Verified via the actual
  live `Content-Security-Policy` response header, not just the source.
- **Dead "Pay Now" button removed.** `type="submit"` with no wrapping
  `<form>` and no JS listener anywhere in `payment-amazon.ts` — clicking it
  did nothing. The real action is Amazon's own SDK-rendered button
  (`amazon.Pay.renderButton()`); the fake button was pure visual clutter.
- **Not fixed — external, not a code defect**: `storeId` and
  `gateway_amazon_pay_returnUrl` were empty in this environment's settings.
  Confirmed via live headers that the CSP fix alone wasn't sufficient —
  Amazon's own servers can't fetch real button assets or construct a valid
  checkout URL without a fully valid signed payload, which needs `storeId`.
  `gateway_amazon_pay_returnUrl` should be the `paymentinformation/amazonComplete`
  route's absolute base URL (code appends `/{url_key}` itself) —
  `http://localhost/invoice/paymentinformation/amazonComplete` for this
  environment. Filling in real Amazon Seller Central sandbox credentials
  (`storeId`, `clientId`, and passing Amazon's own account-side validation)
  is outside what's debuggable from this codebase and was parked as a
  config-blocked, not code-blocked, item.

## Summary

| Gateway | Code bugs found & fixed | Status |
|---|---|---|
| Stripe | 7 (secret encryption, FK seed, invoice-number reference, write ordering, false-failure race, false auto-update promise, CSS centering) | Fully verified live, twice |
| Braintree | 1 (missing CSRF token) | Fully verified live |
| Mollie | 0 (proactive fix from the Stripe pass applied) | Fully verified live |
| Amazon Pay | 1 (CSP `img-src` gap) + 1 dead-code removal | Code confirmed clean; live payment blocked on external Amazon account setup |
