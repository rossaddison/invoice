# Adyen Guest Payment — Session `countryCode` Fix — July 2026

## Symptom

On the guest invoice page, selecting **Adyen** as the payment method loaded
the Drop-in normally, but choosing certain payment methods (observed with
**Pay by Bank**) failed immediately with Adyen's generic red-cross error UI.
No server-side error was logged — `AdyenPaymentController::adyenInForm()` had
already rendered successfully, so the failure was purely client-side.

## Root cause

Browser DevTools traced the failure to a `422` response from
`POST /v1/sessions/{id}/payments`:

```json
{
    "status": 422,
    "errorCode": "200",
    "message": "Field 'countryCode' is not valid. [This error message is only
        provided on TEST, this error will be a 500 Internal Error on LIVE.]",
    "errorType": "validation"
}
```

`AdyenPaymentController::resolveCountryCode()` correctly resolves the
client's country to a 2-letter ISO code (falling back to `GB`), but that
value was only ever passed to the **front-end** `AdyenCheckout()` config in
`payment-adyen.ts` — never to the **session creation** call in
`AdyenPaymentService::createSession()`. Without `countryCode` on the
`CreateCheckoutSessionRequest` itself, Adyen returns its full, unfiltered
list of enabled payment methods for the merchant account — including
country-restricted methods like Pay by Bank (US-only) — regardless of the
session's actual country. The front-end config's `countryCode` only drives
Drop-in's localisation, not which methods the session offers, so selecting
one of those mismatched methods fails at submission.

## Fix

- `AdyenPaymentService::createSession()` now takes a `$countryCode`
  parameter and calls `$request->setCountryCode($countryCode)`.
- `AdyenPaymentController::createAdyenSession()` now resolves the country
  code *before* creating the session and passes it through, returning it in
  the session context array so `adyenInForm()` reuses the same value for the
  front-end config instead of resolving it twice.

With `countryCode` set at session creation, Adyen filters the returned
payment methods to ones valid for that country, so a GB session no longer
offers a US-only method that can never actually complete.

## Verification

- `vendor/bin/psalm --no-cache` clean on both changed files.
- Existing Adyen PHPUnit suite (22 tests) passes unchanged.
- Root cause confirmed live via browser DevTools Network tab against
  `https://yii3i.online` (test/sandbox Adyen environment) — Adyen's Client
  Key Allowed Origins list already correctly included both `http://localhost`
  and `https://yii3i.online`, ruling out CORS before the real cause
  (unfiltered payment methods) was found via the `422` response body.
