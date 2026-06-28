# Quick Pay — Per-Row Inline & Bulk Toolbar Payment

## Summary

Two complementary fast-pay flows added to `inv/index`, designed for the common
case where a customer pays by bank transfer (Faster Payments, BACS, SEPA Credit
Transfer) outside any integrated payment provider:

| Flow | Trigger | Scope |
|------|---------|-------|
| Per-row inline | 💰 button next to Status column | Single invoice |
| Bulk toolbar | ☑️💰 **Quick Pay** button → modal | All checked invoices |

Both flows call `PaymentService::savePayment()` and `InvRecalculator::recalculate()`
so `inv_amount.paid`, `inv_amount.balance`, and `invoice.status_id` are all updated
immediately.

---

## User Flow — Per-Row Inline

1. On `inv/index`, find an invoice with status **Sent** (2), **Viewed** (3),
   **Overdue** (5), or **Partially Paid** (6) and a non-zero total.
2. Click the 💰 button in the new column immediately to the right of Status.
3. An inline form expands in the same cell:
   - **Date** — pre-filled with today (`Y-m-d`), required.
   - **Bank Ref** — free-text note, optional.
   - **✓** — submit; replaces the form with a `✅ YYYY-MM-DD` success badge.
   - **✕** — cancel; restores the 💰 button.
4. Invoices already at status **Paid** (4) or fully paid show `✅` immediately
   (no button) and are not editable through this flow.

The form is rendered server-side by `inv/quickpayform` via HTMX (`hx-get` /
`hx-swap="innerHTML"`). The submit action calls `inv/quickpay`, which returns
either the success badge or a red `✗` badge.

---

## User Flow — Bulk Toolbar

1. Tick one or more checkboxes on `inv/index`.
2. Click **☑️💰 Quick Pay** in the toolbar (beside the other bulk-action buttons).
3. A Bootstrap 5 modal opens with:
   - **Payment Date** — pre-filled with today; clicking anywhere on the field
     opens the browser native calendar (`HTMLInputElement.showPicker()`).
   - **Bank Ref** — free-text note, optional.
4. Click **💰 Quick Pay** to confirm.
5. TypeScript collects the checked invoice IDs from the page, GETs
   `inv/bulkquickpay?keylist[]=…&date=…&note=…`, and reloads the page on success.

---

## PHP Implementation

### `src/Invoice/Inv/Trait/MultipleCopy.php`

Three new action methods, each injected via Yii3 DI:

**`quickpayform()`** — returns raw HTML (not JSON); either the inline form or the
cancel-restored button. Uses two local variables (`$hxTarget`, `$hxSwap`) to avoid
S1192 string-literal duplication.

**`quickpay()`** — loads the invoice to obtain its `payment_method_id`, saves a
`Payment` via `PaymentService::savePayment()`, calls
`InvRecalculator::recalculate()`, and returns a `✅` badge or a `✗` error badge.

**`bulkquickpay()`** — iterates `keylist[]`, loads each invoice, saves a payment,
and recalculates. Returns `{"success": 1}` or `{"success": 0}`.

Payment method resolution in both write methods:

```php
$pmId = $inv->getPaymentMethod() ?? 0;
$paymentService->savePayment(new Payment(), array_filter([
    'inv_id'            => $invId,
    'payment_date'      => $date,
    'amount'            => $invAmount->getTotal() ?? 0.00,
    'note'              => $note,
    'payment_method_id' => $pmId > 0 ? $pmId : null,
], static fn (mixed $v): bool => $v !== null));
```

`array_filter` omits `payment_method_id` entirely when the invoice has no payment
method set, leaving the FK column `NULL` in the database rather than storing a
spurious `0`.

### `src/Infrastructure/Persistence/Payment/Payment.php`

```php
// Before
#[BelongsTo(target: PaymentMethod::class, nullable: false, fkAction: 'NO ACTION')]

// After
#[BelongsTo(target: PaymentMethod::class, nullable: true, fkAction: 'NO ACTION')]
```

The DB column `payment_method_id` was already `nullable: true`; the ORM annotation
was stricter than the schema and threw `Cycle\ORM\Exception\Relation\NullException`
whenever `PaymentService::persist()` could not resolve the payment method entity
(e.g. invoice had no payment method set, or `payment_method_id` was not provided).
No DB migration required — only the annotation changed.

### `src/Invoice/Inv/Widget/InvsColumnBuilder.php`

An inline `DataColumn` is inserted immediately after `$this->buildStatusColumn()`
in `buildColumns()`. The column header is a 💰 tooltip label; the content closure:

- Returns `''` for invoices that cannot be paid (wrong status, zero total).
- Returns a `✅` badge for already-paid invoices (status 4 or paid ≥ total).
- Returns an HTMX-wired 💰 button (wrapped in `<div id="qp-{invId}">`) for
  payable invoices.

The column was inlined rather than extracted to a private method to stay within
SonarQube S1448 (max 20 methods per class).

### `src/Invoice/Inv/Widget/InvsToolbar.php`

- `$bulkQuickPay` — an `<a>` tag that opens `#modal-bulk-quick-pay` via
  `data-bs-toggle="modal"`.
- `$bulkQuickPayModal` — Bootstrap 5 modal HTML built with `Yiisoft\Html\Html`.
  The date input carries `'onclick' => 'this.showPicker()'` so clicking anywhere
  on the field opens the browser native calendar (Chrome 99+, Edge 99+,
  Firefox 101+, Safari 16+).
- The modal is appended after `(new Form())->close()` so it sits outside the
  toolbar `<form>` element.

### `config/common/routes/routes.php`

Three new GET routes, all guarded by the `edit_invoice` permission:

```php
Route::methods([$mG], '/inv/bulkquickpay')  ->action([InvController::class, 'bulkquickpay'])
Route::methods([$mG], '/inv/quickpayform')  ->action([InvController::class, 'quickpayform'])
Route::methods([$mG], '/inv/quickpay')      ->action([InvController::class, 'quickpay'])
```

### `src/typescript/invoice.ts`

`handleClick()` gains a branch for `#bulk-quick-pay-confirm`:

```typescript
const bulkQuickPay = closestSafe(target, '#bulk-quick-pay-confirm');
if (bulkQuickPay) {
    void this.handleBulkQuickPay();
    return;
}
```

`handleBulkQuickPay()` collects all checked `[data-key]` checkbox values, reads
`#bulk-quick-pay-date` and `#bulk-quick-pay-note`, builds the query string, GETs
`inv/bulkquickpay`, and calls `location.reload()` on `{ success: 1 }`.

### `resources/messages/en/app.php`

Two new translation keys:

```php
'quick.pay' => 'Quick Pay',
'bank.ref'  => 'Bank Ref',
```

---

## SonarQube Compliance

| Rule | Issue | Resolution |
|------|-------|------------|
| S1448 | `InvsColumnBuilder` would have reached 21 methods | Inlined `DataColumn` in `buildColumns()` instead of adding `buildQuickPayColumn()` |
| S1192 | `' hx-target="#'` and `' hx-swap="innerHTML"'` each appeared 3× in `quickpayform()` | Extracted to `$hxTarget` and `$hxSwap` local variables |

Psalm errorLevel 1: zero errors on all changed files.

---

## When Quick Pay Does Not Apply

- Invoice status is **Draft** (1) or **Cancelled** (7) → column cell is blank.
- Invoice total is 0 → column cell is blank.
- Invoice is already **Paid** (4) or `paid ≥ total` → shows `✅` (no button).
- Invoice has no payment method set and no fallback is available → the payment
  saves with `payment_method_id = NULL`; this is valid.

For invoices that require a specific payment method or partial amounts, use the
full **Enter Payment** form accessible from the toolbar on `inv/view` or via
**Options → Enter Payment** on `inv/index`.
