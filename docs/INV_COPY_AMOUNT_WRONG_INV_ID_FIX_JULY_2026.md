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

## Follow-up bug found during live verification

Fixing the `inv_id` value above surfaced a second, deeper, pre-existing bug:
live-testing the checkbox copy immediately threw a hard fatal error instead
of the earlier silent-wrong-value symptom:

```
Cycle\ORM\Exception\Relation\NullException: Relation `invAmount`.`inv`
(Cycle\ORM\Relation\BelongsTo)->inv can not be null.
```

`InvAmount.inv` is declared `#[BelongsTo(target: Inv::class, nullable:
false)]` — a required relation *object*, separate from the plain `inv_id`
scalar column. `InvAmountService::saveInvAmountViaCalculations()` (the only
caller is `invToInvInvAmount()` above) only ever called
`$model->setInvId(...)`, never `$model->setInv($invEntity)`. Cycle's
UnitOfWork requires the relation object to be set at persist time for a
non-nullable `BelongsTo`, independent of the scalar column value — so this
was always broken, for any `inv_id` value. It likely didn't surface as a
hard crash under the original (wrong `inv_id`) code because that value
pointed at an invoice already resolvable elsewhere in Cycle's identity map
for that request; pointing it at the freshly-created copy's own id exposed
the missing relation.

**Fix**: `saveInvAmountViaCalculations()` now calls the existing private
`persist()` helper first (already used correctly by `saveInvAmount()`),
which loads the `Inv` entity by id and calls `$model->setInv($invEntity)`
before the scalar setters run.

## Verification

- `vendor/bin/psalm --no-cache` clean on both changed files.
- `Tests/Unit/QuoteToSalesOrderToInvoiceWorkflowTest.php` (12 tests, the only
  existing coverage touching `InvAmountService`) passes unchanged.
- Confirmed live: reproduced the exact `NullException` via checkbox-copy on
  `inv/index` before this second fix; recommend re-confirming after deploy
  that copying no longer throws and the amount displays correctly
  immediately, without opening the new invoice first.
