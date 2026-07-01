# Credit Note Workflow — `read_only_toggle` Interaction

## Overview

A credit note (credit invoice) reverses a previously issued invoice. The
**Create Credit Invoice** button appears on the invoice view toolbar and is
controlled by three conditions in `ButtonsToolbarFull::render()`:

```php
if (($read_only === true || $inv->reqStatusId() >= 2)
        && $invEdit
        && !(int) $inv->getCreditinvoiceParentId() > 0)
```

| Condition | Meaning |
|---|---|
| `$read_only === true` **OR** `reqStatusId() >= 2` | Invoice is marked read-only in the DB **or** has progressed past Draft (Sent / Viewed / Paid) |
| `$invEdit === true` | Logged-in user has the `editInv` RBAC permission |
| `getCreditinvoiceParentId()` is 0 / null | No credit note has already been created from this invoice |

## `read_only_toggle` Setting

Found in **Settings → Invoices → Set to Read Only**. Controls *when* the
`is_read_only` flag is written to the DB for each invoice:

| Value | Label | When `is_read_only` is set |
|---|---|---|
| `2` | Sent (Peppol Requirement) | At the moment the invoice is emailed |
| `3` | Viewed | When the client views via the guest URL key |
| `4` | Paid (Relaxed / General Use) | When payment clears the balance to zero |

### Peppol compliance note

Under Peppol BIS Billing 3.0 / EN16931 / UBL 2.4, a transmitted invoice
must not be altered. **Sent** is the compliant default — once dispatched the
invoice is immutable and a credit note is the only correction mechanism.
**Paid** suits general use where strict immutability before payment is not
required.

## How the Setting Interacts with Existing Invoices

The setting is **prospective only**. It is checked at the moment of each
triggering event (`invoiceMarkSent`, `invoiceMarkViewed`,
`inv_balance_zero_set_to_read_only_if_fully_paid`). It does **not**
retroactively update `is_read_only` on existing invoice records.

### Effect of changing the setting mid-lifecycle

| Transition | Existing invoices | Future invoices |
|---|---|---|
| Paid → Sent | Already-locked invoices stay locked (`is_read_only = true`). Invoices sent while toggle was Paid stay unlocked (`is_read_only = false`). | Locked immediately on send. |
| Sent → Paid | Already-locked invoices remain locked. | Not locked until payment clears. |

**Orphan risk (Paid → Sent):** invoices sent while the toggle was Paid have
`is_read_only = false` and status 2. Previously only `status === 4` triggered
the credit button, so those invoices had no credit path without manually
forcing them to Paid status.

**Fix applied (July 2026):** the credit button condition was widened from
`reqStatusId() === 4` to `reqStatusId() >= 2`, so the button now appears on
any Sent (2), Viewed (3), or Paid (4) invoice regardless of whether
`is_read_only` was correctly set at send time.

## Bulk DB Alignment After a Setting Change

If migrating an existing database to a stricter or more relaxed rule, a
targeted update is needed — the setting change alone is insufficient:

```sql
-- After switching Paid → Sent: lock all previously-sent invoices
UPDATE inv SET is_read_only = 1 WHERE status_id >= 2 AND is_read_only = 0;

-- After switching Sent → Paid: unlock invoices not yet paid
UPDATE inv SET is_read_only = 0 WHERE status_id < 4 AND is_read_only = 1;
```

Run these only after taking a DB backup. A future UI improvement could offer
a confirmation prompt when the setting is changed in order to apply the
alignment automatically.

## Credit Note Creation Flow

1. Open an invoice at status Sent / Viewed / Paid (or `is_read_only = true`).
2. Click **Create Credit Invoice** on the toolbar.
3. The modal (`modal_create_credit.php`) shows the client, date, and the Credit Note Group (pre-selected by name via `repoGroupByName('Credit Note Group')`, **not** by the `default_invoice_group` setting).
4. On confirm, `createCreditConfirm()` in `Trait/Credit.php`:
   - Resolves the Credit Note Group by name (`GroupRepository::repoGroupByName('Credit Note Group')`), never trusting the request body for the group.
   - Creates the new credit invoice via `InvService::saveInv()`.
   - Copies items, amounts, and tax rates from the original.
   - Sets `creditinvoice_parent_id` on the **original** invoice to the new
     credit note's ID, preventing a second credit note being created.
5. Both invoices are marked `is_read_only = true`.

## Known Pitfalls

### Credit button missing despite invoice being read-only and sent (pre-July 2026)

The old condition `reqStatusId() === 4` meant a Sent invoice with
`is_read_only = false` (set before the toggle was configured to Sent) showed
no credit button. The workaround was to manually set the invoice status to
Paid. Fixed by widening the condition to `>= 2`.

### Fatal error when invoice group does not exist

`GroupRepository::generateNumber(int $id)` called with a group ID that has no
matching DB record produced `Call to a member function getIdentifierFormat() on null`.
The fix: `createCreditConfirm()` resolves Credit Note Group by name via
`repoGroupByName('Credit Note Group')` and returns a JSON error response if the
group is missing, rather than passing a stale or wrong ID to `generateNumber()`.
A `\RuntimeException` with a descriptive message is also thrown inside
`generateNumber()` itself as a final safety net.

### Credit note created with `INV` identifier instead of `CN` (regression July 2026)

When the original crash (`getIdentifierFormat() on null`) was fixed by removing the
hardcoded `group_id = 4`, the replacement read `group_id` from the request body.
The modal's hidden `<select name="inv_group_id">` was pre-selecting by the
`default_invoice_group` setting (typically Invoice Group, id = 1, format
`INV{{{id}}}`), so `$body['group_id']` was `1`, and `generateNumber(1)` produced
`INV` identifiers on credit notes.

**Lesson:** never trust the request body for the credit note group. Always resolve
it server-side by name (`repoGroupByName('Credit Note Group')`). The modal select
is for display only and must also be pre-selected by name, not by
`default_invoice_group`.

### `read_only_toggle` changed without DB alignment

See [Bulk DB Alignment](#bulk-db-alignment-after-a-setting-change) above.
