# Payment Record Channel Emoji — Charset Regression Fix (August 2026)

## The regression

`f05c501f` (2026-08-06, "Add webhook vs redirect emoji marker to recorded
payment notes") prepended a `🪝` (U+1FA9D, HOOK) emoji onto every
webhook-channel `Payment.note`. `PaymentRecordContext`'s `channel` defaults
to `PaymentRecordChannel::Webhook` for 9 of this app's 11 gateway
integrations (Robokassa, YooKassa, Paystack, Razorpay, PayPal, Mollie,
Stripe, Adyen, GoCardless), so this affected almost every gateway's
automatic paid-marking, not just one.

`🪝` is a genuinely 4-byte UTF-8 character. As documented in
[MYSQL_CONNECTION_CHARSET_BUG_AUGUST_2026.md](MYSQL_CONNECTION_CHARSET_BUG_AUGUST_2026.md),
this app's live MySQL connection has been found to silently negotiate a
narrower charset than the schema actually supports (every column,
including `payment.note`, is genuinely `utf8mb4`; the connection itself
was the gap). Before `f05c501f`, nothing this app ever wrote touched a
4-byte character, so that latent gap was never triggered. From
2026-08-06 onward, every real webhook-driven payment attempted to write
this emoji and hit MySQL 1366 (`Incorrect string value`), which aborted
`PaypalWebhookHandler::markInvoicePaidIfVerified()` (and the equivalent
method in every other webhook handler) *before* the invoice got marked
paid — so payments that had genuinely gone through at the gateway stopped
showing as paid in this app, silently, for every gateway.

**Live user report confirming this wasn't PayPal-specific**: "The paid
was appearing beforehand automatically via other payment_gateways. Now
it is not" — Mollie had been working via `MollieWebhookHandler` up to
2026-08-05 (`007cceea`), one day before the emoji commit landed.

## Fix

Rather than depend on the still-unresolved question of why the
[DSN charset fix](MYSQL_CONNECTION_CHARSET_BUG_AUGUST_2026.md#fix) hasn't
provably taken effect against real webhook traffic on production, this
fix removes the actual trigger: `PaymentRecordChannel::Webhook->emoji()`
now returns `⚡` (U+26A1, HIGH VOLTAGE SIGN) instead of `🪝`. U+26A1 sits
in the Miscellaneous Symbols block, part of the Basic Multilingual Plane
— every BMP codepoint is at most 3 bytes in UTF-8, so it's safe
regardless of what the connection's actual negotiated charset turns out
to be. `PaymentRecordChannel::Redirect->emoji()` (`↩️`, U+21A9 U+FE0F) was
already BMP-safe and is unchanged — this is exactly why Braintree's
synchronous card-nonce flow and Mollie's legacy redirect fallback path
never hit this bug even before this fix.

Two new regression-guard tests (`webhookEmojiIsBmpSafe()`,
`redirectEmojiIsBmpSafe()`) assert every codepoint in both emoji is
`<= U+FFFF`, so a future emoji change can't silently reintroduce this
exact class of bug.

**This is a tactical fix, not a resolution of the underlying issue.**
The connection-charset gap documented in
`MYSQL_CONNECTION_CHARSET_BUG_AUGUST_2026.md` is still real and still
unexplained why it persists against real webhook traffic despite the DSN
fix being present and verified correct via a live, direct PDO test on
production. Any other 4-byte UTF-8 character written anywhere else in
this app (a client name using an astral-plane character, for instance)
would still hit the identical crash. That deeper fix remains outstanding.

## Verification

`php -l` clean. Full-project `vendor/bin/psalm --no-cache`: no errors.
Full Testo suite: 807/807 passing (805 existing + 2 new BMP-safety
guards).
