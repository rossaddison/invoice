# PayPal Webhook `decode()` Bug (August 2026)

## The incident

Live sandbox testing of the PayPal gateway (see
[PAYPAL_CAPTURE_ERROR_LOGGING_AUGUST_2026.md](PAYPAL_CAPTURE_ERROR_LOGGING_AUGUST_2026.md)
for the sibling fix from the same testing session) found that **every
single real PayPal webhook delivery** — `PAYMENT.CAPTURE.COMPLETED`,
sent by PayPal's own servers after a real sandbox checkout — returned
HTTP 500 from this app. Two invoices (`INV119`, `INV120`) were
successfully paid via PayPal's hosted checkout, but the app's own
database never learned this: both stayed `sent`/unpaid.

The investigation initially chased two red herrings before finding the
real cause:

1. **A wrong webhook URL.** I (Claude) incorrectly told the user to
   register the webhook under an `/invoice/`-prefixed URL, wrongly
   generalizing from `paypalComplete`'s redirect URL (which genuinely
   is inside `RoutePermission::invoiceGroup()`'s `/invoice` route
   group). The webhook route
   (`/paymentinformation/paypalWebhook`) is registered *outside* that
   group and has no such prefix. The user's own instinct that the URL
   was the problem was correct; my specific guidance was not. Fixed by
   reverting the Dashboard's webhook URL to the correct un-prefixed
   one.
2. **Route-cache staleness.** Production (`YII_ENV=prod`) caches
   FastRoute's dispatch table via APCu with no auto-invalidation after
   a deploy (see
   [YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md](YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md)).
   The user restarted Apache per that doc's guidance. This didn't fix
   it either, because it wasn't the actual cause here.

The real cause only surfaced from reading production's own log
(`/var/www/invoice/runtime/logs/app.log`) directly over SSH. Every
failed webhook delivery's stack trace pointed to the exact same line:

```
PaypalWebhookHandler.php(53): App\Invoice\Setting\SettingRepository->decode()
```

## Root cause

`PaypalWebhookHandler::handle()` read the configured PayPal webhook ID
like this:

```php
$webhookId = (string) $this->sR->decode($this->sR->getSetting('gateway_paypal_webhookId') ?: '');
```

`SettingRepository::decode()` (via `SettingMiscTrait::decode()`) is
only meant to be called on a Setting whose field `type` is
`'password'` — those values are genuinely encrypted at rest with
`Cryptor::Encrypt()` on save, so reading them back requires the
matching `Cryptor::Decrypt()`.

`gateway_paypal_webhookId`, however, is declared `'type' => 'text'` in
`SettingPaymentTrait::paypalGatewayFields()` — a plain, unencrypted
value, exactly like `clientId` (also `'text'`, never decoded anywhere
in `PaypalPaymentService`). Only `clientSecret` (`'password'`) is
genuinely decoded, in `PaypalPaymentService::clientSecret()`.

Feeding a plain string like `"32L62775KU8374611"` (PayPal's own
webhook ID format) into `Cryptor::Decrypt()` throws, because it isn't
valid ciphertext. Since this call sits directly in `handle()`, before
any try/catch, the exception propagated all the way out as an
unhandled 500 on every incoming webhook — signature verification never
even got a chance to run.

This is a variant of the same bug class as the
[capture error logging incident](PAYPAL_CAPTURE_ERROR_LOGGING_AUGUST_2026.md):
a real gap only found by testing an actual, live, third-party callback
against production, not something a local mock-driven test would ever
exercise (the Testo suite's PayPal webhook tests construct
`$webhookId` directly, bypassing `SettingRepository` entirely, so
they were never in a position to catch this).

## Fix

```php
$webhookId = $this->sR->getSetting('gateway_paypal_webhookId') ?: '';
```

Read the setting directly, with no `decode()` call — matching how
`clientId` (also `'text'`) is already read elsewhere in
`PaypalPaymentService`.

## Audit of the rest of the codebase

Every other `decode()` call site under `src/Invoice/PaymentInformation/`
was cross-referenced against its Setting field's declared `type` in
`SettingPaymentTrait.php` to confirm no other instance of this bug
class exists:

| Gateway | Field decoded | Declared type | Correct? |
|---|---|---|---|
| Adyen | `clientKey` | `password` | ✓ |
| Amazon Pay | `merchantId`, `storeId` | `password` | ✓ |
| Braintree | `merchantId`, `publicKey`, `privateKey` | `password` | ✓ |
| Mollie | `testOrLiveApiKey` | `password` | ✓ |
| GoCardless | `accessToken` | `password` | ✓ |
| Robokassa | `password1`/`password2`/`password3` | `password` | ✓ |
| Paystack | `secretKey` | `password` | ✓ |
| YooKassa | `secretKey` | `password` | ✓ |
| Razorpay | `keySecret` | `password` | ✓ |
| Mercado Pago | `accessToken`, `webhookSecret` | `password` | ✓ |
| Square | `accessToken`, `webhookSecret` | `password` | ✓ |
| Stripe | `apiKey`, `publishableKey`, `secretKey`, `webhookSecret` | `password` | ✓ |
| StoreCove | `apiKey` | `password` | ✓ |
| **PayPal** | **`webhookId`** | **`text`** | **✗ — the bug fixed here** |

PayPal's `webhookId` was the only `'text'`-typed field anywhere in the
codebase that was being passed through `decode()`. No other instance
of this bug class was found.

## Verification

`php -l` clean. Full-project `vendor/bin/psalm --no-cache`: no errors.
Full Testo suite: 805/805 passing (no regressions; this fix has no
Testo coverage of its own since the existing webhook tests construct
`$webhookId` directly rather than through `SettingRepository` — see
above).

Requires a production deploy (`git pull` + Apache restart, per
[YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md](YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md))
before a real PayPal webhook can be retested end-to-end against
`INV119`/`INV120`.
