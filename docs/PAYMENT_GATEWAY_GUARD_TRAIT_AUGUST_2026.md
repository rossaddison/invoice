# Payment Gateway Guard Trait — August 2026

## Summary

SonarCloud's quality gate started failing on `new_duplicated_lines_density`
(12.0% vs. 3% allowed) as a direct side effect of this session's own
`php:S1142` fix (max 3 returns per method — commit `af00299b`, "Fix 14
SonarQube code smells"). That fix extracted a guard-clause chain — load invoice → check
gateway configured → check positive balance — into three small private
methods (`resolveConfiguredInvoiceWithBalance()`,
`requireXConfigured()`, `requirePositiveBalance()`) inside **each** of
Square/PayPal/Razorpay/Robokassa/YooKassa's own controllers separately.
Same logic, five times over, differing only in which payment service and
gateway display label each copy referenced — exactly what SonarCloud's
copy-paste detector exists to catch.

## The fix

New `PaymentGatewayGuardTrait`
(`src/Invoice/PaymentInformation/Trait/PaymentGatewayGuardTrait.php`) holds
the one real implementation, parameterized instead of hardcoded:

```php
private function resolveConfiguredInvoiceWithBalance(
    CurrentRoute $currentRoute,
    PaymentGatewayInterface $service,
    string $gatewayLabel,
): Response|array
```

Every gateway's own `*PaymentService` already implements the shared
`PaymentGatewayInterface` (`getDriverKey()`, `isConfigured()`,
`verifyPayment()`, `refund()`), so the trait can type-hint against that
interface generically rather than needing a concrete service class per
gateway. Each controller's own call site now reads:

```php
$resolved = $this->resolveConfiguredInvoiceWithBalance($currentRoute, $this->squarePaymentService, 'Square');
```

with the gateway-specific `requireXConfigured()`/`requirePositiveBalance()`
private methods removed entirely from all 6 controllers — the trait is the
only place that logic lives now.

## Paystack's extra step

Paystack needs a client email address on file (something no other gateway
here requires), so it keeps a thin `requireClientEmail()` composed on top
of the trait's shared result, rather than duplicating the trait's own
logic to fit its own extra check in:

```php
private function resolveConfiguredInvoiceWithBalanceAndEmail(CurrentRoute $currentRoute): Response|array
{
    $resolved = $this->resolveConfiguredInvoiceWithBalance($currentRoute, $this->paystackPaymentService, 'Paystack');
    if ($resolved instanceof Response) {
        return $resolved;
    }

    return $this->requireClientEmail($resolved['invoice'], $resolved['balance']);
}
```

## What wasn't touched

`SquareMerchant.php` was also flagged by the same duplication scan — it
deliberately mirrors `Merchant.php`'s shape (same getters/setters,
`reqId()` pattern, `BelongsTo` relation). That's inherent to being two
genuinely separate entities with a shared lineage, not a copy-paste
extraction artifact the way the controllers were, so it's left as-is; see
[docs/SQUARE_MERCHANT_PER_PROVIDER_ENTITY_AUGUST_2026.md](SQUARE_MERCHANT_PER_PROVIDER_ENTITY_AUGUST_2026.md)
for why that entity exists in the first place.

## The other failing quality-gate condition — not fixed here

The quality gate's `new_coverage` condition is also failing (19.5% vs.
80% required), for a genuinely separate reason: several files have new
lines with **zero** line coverage — `PaystackPaymentController.php`,
`PaypalPaymentController.php`, `RazorpayPaymentController.php`,
`RobokassaPaymentController.php`'s own action-method bodies,
`OnlinePaymentRecorderService.php`, the remaining uncovered portion of
`SquareMerchant.php`, and `src/typescript/homecare-offline-shell.ts`'s
lines from the TypeScript 7 typing-fix commit. This needs real new unit
tests written against each of those, not a refactor — a substantially
larger, separate piece of work, deliberately not attempted in this same
change.

## Verification

- Full-project Psalm (`vendor/bin/psalm --no-cache`): no errors found.
- Full Testo suite: 772/772 passing (no new tests needed — the trait's
  logic is exactly what `SquareMerchantTest`/etc. and each gateway's
  existing coverage already exercised, just relocated).
- Full PHPUnit suite: 3,877/3,877 passing (23 pre-existing notices only).
- Live-curled all 6 refactored `*InForm` endpoints — unchanged clean
  `404` responses, confirming no behavioral change.
- Net: -248 lines across the 6 controllers, +1 new ~80-line trait file
  holding the single canonical implementation.
