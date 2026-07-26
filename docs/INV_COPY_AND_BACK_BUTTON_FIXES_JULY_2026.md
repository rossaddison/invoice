# Invoice Checkbox-Copy — Full Bug Hunt, and Back-Button Fixes — July 2026

## Background

Live, hands-on testing of `inv/index`'s checkbox copy feature (create an
invoice, copy it, inspect the result directly in the database) surfaced a
chain of real, pre-existing bugs — most dating back to the original DDD
infrastructure migration (May 2026), well before this session. Each fix
exposed the next, since earlier bugs were masking later ones (e.g. a crash
early in the copy pipeline meant later steps never ran to reveal their own
defects). Confirmed via direct MySQL queries against the local database
throughout, not just UI observation.

## 1. `InvAmount.inv_id` pointed at the original invoice, not the copy

`invToInvInvAmount()` in `src/Invoice/Inv/Trait/MultipleCopy.php` built its
save array with `inv_id` taken from the *original* invoice's `InvAmount`
rather than the copy's own id. Since `Inv` has a `HasOne` relation to
`InvAmount` keyed on `inv_id`, this overwrote the copy's own foreign key to
point at the original, detaching it from the new invoice. `inv/index` found
no matching row and showed nothing/zero until the invoice was opened once,
which recalculates and re-saves with the correct id — masking the bug.

## 2. Missing Cycle ORM relation object caused a hard crash

Fixing #1 alone turned the bug into a `Cycle\ORM\Exception\Relation\NullException`
on every checkbox-copy. `InvAmount.inv` is a required (`nullable: false`)
`BelongsTo` relation — a separate concern from the plain `inv_id` scalar
column. `InvAmountService::saveInvAmountViaCalculations()` only ever called
`setInvId()`, never `setInv($entity)`, so Cycle's UnitOfWork had no relation
object to persist.

## 3. Root-cause refactor: mirror `SalesOrderToInvoiceConverter`

Rather than patch around #1/#2 further, `invToInvInvAmount()` was rewritten
to mirror the already-correct, already-proven
`SalesOrderToInvoiceConverter::soToInvoiceSoAmount()` pattern: operate on
each `Inv`'s own already-attached `InvAmount` relation object
(`$inv->getInvAmount()`) and save through the parent `Inv` entity, instead of
re-fetching a detached `InvAmount` via a separate repository call. This
eliminates the whole class of bug at the root. `saveInvAmountViaCalculations()`
became dead code as a result and was deleted.

## 4. Invoice-level tax silently dropped on every copy

`invToInvInvTaxRates()` built its copy array with the key `'amount'`, but
`InvTaxRateForm` (and `InvTaxRateService::saveInvTaxRate()`) require the key
`inv_tax_rate_amount` — a `#[Required]` field. The mismatched key meant
validation silently failed and **no `InvTaxRate` row was ever created** for
any copy, dropping the invoice-level tax entirely rather than just
miscalculating it. Confirmed against `SalesOrderToInvoiceConverter::soToInvoiceSoTaxRates()`,
which already used the correct key name.

## 5. Invoice-level allowance/charges never copied at all

`copyInvToClient()` (the checkbox `multiplecopy()` path) simply never called
`invToInvInvAllowanceCharges()` — an outright omission, inconsistent with the
other two copy functions in the same trait (`saveInvConfirmCopy()`,
`generateHomeCareCleaningInvoice()`), which both already called it. Item-level
allowance/charges (`inv_item_allowance_charge`) copied correctly throughout;
only the invoice-level ones (`inv_allowance_charge`) were affected.

## 6. Item-level tax formula disagreed with the interactive "add charge" UI

Comparing a copied item's amounts against the original in the database
surfaced a genuine formula inconsistency, independent of the copy feature
itself: `InvItemService::saveInvItemAmount()` applied the item's own
`tax_rate_percentage` to the *charge-inclusive* subtotal, while
`InvItemAllowanceChargeController::performAddSave()`/`performEditSave()`
(the interactive "add allowance/charge" UI) applies it only to the item's
own subtotal and then adds each charge/allowance's own `vat_or_tax` directly.
These only produced the same result when a charge/allowance's own tax rate
happened to match the item's — otherwise the copy silently computed the
wrong item tax. Rewrote `saveInvItemAmount()` to match the UI's formula
(confirmed correct per user decision): sum each charge's/allowance's own
`vat_or_tax`, add it to the item's own tax-rate-based tax, rather than
applying the item's tax rate to the charge-adjusted subtotal.

**Not yet fixed**: `InvItemController::saveInvItemAmount()` (used for direct
item edits, not copies) contains a *third*, still-divergent copy of this same
formula — its own docblock already flags the duplication ("any adjustments
... should be reflected also in the similar InvItemService function"). Left
untouched since it's outside the copy path; worth reconciling separately.

## 7. "Back" button silently did nothing on `inv/view` and `quote/view`

Both views had a `data-bs-toggle="tab"` link with `onclick="window.history.back()"`
and no `href`, inside the same Bootstrap tabs nav as genuine in-page tab
switches (add product/add task). Bootstrap's Tab plugin intercepts clicks on
*any* element with `data-bs-toggle="tab"` and calls `preventDefault()`,
regardless of `href` — so the link never navigated anywhere, silently.
Fixed by giving both a real `href` to their respective index route
(`inv/index`, `quote/index`) and removing `data-bs-toggle="tab"` from just
this one link in each nav (its siblings still need it, since they genuinely
switch in-page tabs). Audited the rest of the codebase for the same
`data-bs-toggle="tab"` + `history.back()` combination — no other occurrences
found; other `history.back()` usages elsewhere don't have a `data-bs-toggle`
conflicting with them.

`salesorder/view` had no back button at all (no tab nav bar there to begin
with) — added a standalone one in the headerbar, same style, linking to
`salesorder/index`.

## Verification

- Full-project `vendor/bin/psalm --no-cache` clean (fixed two
  `RedundantCondition`/`TypeDoesNotContainNull` findings the full-project run
  caught that per-file runs didn't, since `getItemSubtotal()`/
  `getItemTaxTotal()` are non-nullable `float`).
- `Tests/Unit/QuoteToSalesOrderToInvoiceWorkflowTest.php` (12 tests) passes.
- Confirmed live against the local database throughout: created fresh
  invoices, copied them via the checkbox, and inspected `inv_amount`,
  `inv_tax_rate`, `inv_allowance_charge`, and `inv_item_amount` rows directly
  via `mysql` to verify each fix rather than relying on UI appearance alone.
