# Invoice Checkbox-Copy — Wrong `InvAmount.inv_id` Fix — July 2026

## Symptom

On `inv/index`, copying an invoice via the checkbox selection (multi-copy /
copy-to-client flows) produced a new invoice whose amount/balance did not
show correctly in the list. Opening the new invoice first, then returning to
the list, fixed the display.

## Root cause

`invToInvInvAmount()` in `src/Invoice/Inv/Trait/MultipleCopy.php` is the only
code path that populates a copied invoice's `InvAmount` row (shared by
`multiplecopy()`, `invToInvConfirm()`, and `generateHomeCareCleaningInvoice()`).
It built its save array with:

```php
$array['inv_id'] = $original->reqInvId();
```

`$original` is the **source** invoice's `InvAmount`, so `reqInvId()` returned
the source invoice's own id — not the copy's. `Inv` has a `HasOne` relation
to `InvAmount` keyed on `inv_id` (`Inv::__construct()` attaches a fresh empty
`InvAmount` to every new invoice), so `saveInvAmountViaCalculations()`
overwrote the copy's own `InvAmount.inv_id` foreign key to point at the
*original* invoice instead of itself — detaching it from the new invoice.

`inv/index` looks up each invoice's amount by `inv_id`, so the new invoice
now matched no `InvAmount` row and showed nothing/zero. Opening the invoice
runs `NumberHelper::calculateInv()`, which recalculates from the invoice's
own items and re-saves `InvAmount` with the *correct* `inv_id` — masking the
bug after that first view.

A second, unrelated copy-paste bug was in the same block:
`packhandleship_total` was set from `getPackhandleshipTax()` instead of
`getPackhandleshipTotal()`.

## Fix

- `$array['inv_id']` now uses `$copiedId` (the new invoice's own id).
- `packhandleship_total` now reads `getPackhandleshipTotal()`.

## Verification

- `vendor/bin/psalm --no-cache` clean on the changed file.
- No existing test coverage for this trait; recommend confirming live —
  copy an invoice via the `inv/index` checkbox and confirm the new invoice's
  amount displays correctly immediately, without opening it first.
