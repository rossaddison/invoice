# Bookkeeping Ledger Sync and Invoice Status Model (September 2026)

Follows the QuickBooks OAuth consolidation (PR #1343). Testing the export
against real invoices exposed that the ledger only ever saw invoices settled
through an online gateway, that refunds and credit notes never reached it,
and that the invoice status model (inherited from InvoicePlane) had no way to
cancel an unpaid invoice. This work fixes those together, because each one
depends on the others.

## Invoice status model

Draft, issued, viewed and paid only move forward. `Inv::setStatusId()`
enforces it centrally (about twenty call sites set status), so a bulk action or
a stale form cannot revert an invoice whose ledger entries may already be
exported to QuickBooks. It applies to saved invoices only; dunning stages
(overdue through loss, ids 5-13) are outside the sequence.

| Id | Label | Ledger effect |
|---|---|---|
| 1 | Draft | none |
| 2 | **Issued** (was "Sent") | invoice entry |
| 3 | Viewed | none (still issued) |
| 4 | Paid | payment entry |
| 5-13 | Overdue ... Loss, Credit note (12) | credit note (12) posts its own entry |
| 14 | **Void** (new) | reversal of the invoice entry |

"Sent" described the email, not the accounting fact, so status 2 is now
labelled **Issued** on invoice screens (quotes keep "Sent"; the Peppol "sent"
label stays because that really is a Peppol send). Ids are unchanged, so
existing data and filters keep working.

### Void versus credit note

- **Void** cancels an issued or viewed invoice that has **no payments**. It
  keeps its number (no gaps in the sequence), becomes read-only, and is
  terminal. Available from the invoice's Options menu and, in bulk, from the
  inv/index toolbar (`inv/voidSelected`), which reports the invoices it
  skipped.
- **Credit note** corrects an invoice that has been paid or reported. It is a
  separate document with its own number; the original stays valid.

InvoicePlane had no void, so credit notes were being used to cancel unpaid
invoices, which is a different accounting operation.

The old bulk "sent to draft" action is gone; its endpoint removed and its
toolbar slot is now "Void". The edit form's status dropdown omits moves the
entity would refuse.

## Ledger entries

`InvLedgerSyncService` reconciles the ledger from invoice state, whichever
code path changed it (email, batch, API, manual payment, gateway). Every entry
is keyed by a reference derived from the invoice number, so it is idempotent.

| Trigger | Entry |
|---|---|
| Issued / viewed / dunning | Dr Accounts Receivable, Cr Sales, Cr VAT (dated by the invoice date) |
| Paid | Dr Bank, Cr Accounts Receivable |
| Void | Dr Sales, Dr VAT, Cr Accounts Receivable (only if the invoice entry exists) |
| Credit note | Dr Sales, Dr VAT, Cr Accounts Receivable (absolute amounts) |
| Gateway refund | Dr Sales, Dr VAT, Cr AR, Dr AR, Cr Bank |

The refund is deliberately one entry with **five lines**: it reverses the
invoice (Sales/VAT against AR) and then the payment (AR against Bank), so the
audit trail shows both halves explicitly rather than netting AR to nothing.
It is posted by `PaymentRefundController` at refund time.

New transaction types: `Void`, `CreditNote`.

## Exporting

- `php yii bookkeeping/export` syncs the ledger, then exports whatever is
  queued, printing exported/failed counts and each failure.
- The **Export now** button on Settings, Online Bookkeeping does the same and
  reports the result as flash messages (`POST bookkeeping/export`, `EDIT_INV`).

## Not covered yet

- A gateway refund does not raise a credit note automatically. If a credit note
  is also raised manually for the same invoice, the ledger reverses it twice.
- Quotes and sales orders have their own status setters and were not touched.
- The batch void call is a GET, like the neighbouring inv/index batch actions.

## Verification

Full-project Psalm clean; Testo 1504/1504; PHPUnit 3958/3958; TypeScript
suite 244/244. Not exercised in a browser or against the QuickBooks sandbox
yet.
