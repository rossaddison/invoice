# HomeCare QR Auto-Invoice — Pitfalls Found and Fixed

## Why

A pitfalls review of `docs/HOMECARE_QR_AUTOINVOICE_AND_ROUTES_REFACTOR.md`'s
eligibility rule turned up a chain of real issues, several inspired by how
external systems (Stripe idempotency keys, ride-hailing trip-completion
events, field-service scheduling tools like ServiceM8/Jobber/Squeegee) solve
the same class of problem. This doc records what was found and what changed.

## Pitfalls found

1. **Race condition on rapid/concurrent double-scan.** The old eligibility
   check ran *before and outside* the transaction that created the invoice —
   two near-simultaneous requests for the same token could both read
   "eligible" before either committed, producing two invoices from one scan.
2. **Undocumented hard requirement**: the client needs exactly one active
   linked user account (`activeUser()`), a completely separate prerequisite
   from the payment/Service-item rule, silently surfaced as a generic
   "contact us" page with no indication why.
3. **Zero logging anywhere in the flow** — both "not_eligible" and
   "contact_us" outcomes were completely invisible server-side.
4. **The cycle silently goes dormant** if the client's most recently paid
   invoice happens not to contain a Service-type item.
5. **"Not eligible" and "something's broken" looked identical** to the
   customer and to support, by design (the result page is deliberately
   PII-free) — but that also meant zero triage signal for staff.
6. The QR token itself never expires or rotates (a deliberate tradeoff for
   the "sticker on the bin" use case, not itself fixed here).
7. **Any ordinary invoice admin manually raises for the client blocks the
   automation.** The old rule counted *any* invoice dated after the last
   payment, "regardless of status" (its own docblock's wording) — a
   completely unrelated one-off sale, correction invoice, or abandoned draft
   silently paused that client's QR auto-invoicing.
8. **Issuing a credit note has the identical blocking side effect**, since a
   credit note is just another `Inv` row the same query counts.
9. **Bulk invoice-copy tools amplify #7/#8 to a whole batch at once** — a
   routine "Copy All to Date"/"copy to client(s)" run could silently pause
   automation for every client in the batch simultaneously.
10. **A self-perpetuating duplicate**: soft-deleting a duplicate invoice
    from pitfall #1 made the eligibility rule "forget" it existed (it
    filtered `deleted_at = null`), so the very next scan could regenerate
    another duplicate against the original anchor invoice — the obvious
    cleanup step re-triggered the bug it was fixing.
11. Backdating a payment date retroactively could shift the eligibility
    anchor and generate an invoice out of sequence with what actually
    happened operationally.
12. **No per-client override** — `homecare_auto_invoice_enabled` is
    site-wide only; pausing automation for one problem client meant pausing
    it for every HomeCare client at once.

## What changed

### New `HomeCareVisit` table — the core fix for #1, #7, #8, #9, #10

One row per (client, calendar day), with a **unique DB index on
(client_id, visited_at)** — see
`src/Infrastructure/Persistence/HomeCareVisit/HomeCareVisit.php`. A scan
attempts to *insert* a pending row first
(`HomeCareVisitRepository::tryCreatePendingVisit()`); the unique index
itself is what makes a concurrent or repeat same-day scan safe, not
application-level locking — the losing request simply reads back whatever
outcome the winner already recorded
(`repoFindByClientAndDatequery()`), never re-deciding or regenerating.

Eligibility itself (`HomeCareCleaningEligibilityService`) is now anchored on
this facility's *own* last generated visit/invoice link
(`repoLatestGeneratedVisitquery()`) rather than scanning the client's whole
invoice history — "is the last invoice *this facility generated* paid yet?"
instead of "does any invoice exist after the last payment, regardless of
source?" An admin's unrelated invoice or credit note, or a bulk copy run,
can no longer touch this table at all, so it can no longer interfere.

This also fixes #10: deleting a duplicate invoice no longer un-blocks
anything, since the visit row (not the invoice's mere existence) is what the
rule reads.

### Per-client pause flag — fixes #12

New `Client::homecare_auto_invoice_paused` (bool, default false). The
eligibility rule checks this before anything else, alongside the existing
site-wide `homecare_auto_invoice_enabled` setting.

### Logging + admin-visible scan log — fixes #2, #3, #5

`HomeCareScan::homeCareScan()` now logs a warning (client id, template
invoice id) whenever invoice generation fails, and every scan's outcome
(`generated`/`not_eligible`/`contact_us`) plus a free-text `reason` (e.g.
"no active linked user account") is written to the `HomeCareVisit` row —
readable at **Settings → HomeCare → 📋 HomeCare QR Scan Log**
(`homecarevisit/index`, `EDIT_INV` permission). The customer-facing result
page stays deliberately generic; this log is the staff-only counterpart.

### Backward search for a Service item — fixes #4 (August 2026 follow-up)

`HomeCareCleaningEligibilityService::findInvoiceToCopyIfEligible()` no
longer requires the single most-recent paid invoice to contain a
Service-type item. It now walks the client's paid invoices, most recent
first, via a new `InvRepositoryInterface::repoClientPaidInvoicesquery()`
(capped at the most recent 50), returning the first one whose items
include a Service product — via a new private
`findMostRecentPaidInvoiceWithServiceItem()`. A client whose latest paid
invoice happens to be an unrelated one-off, non-Service sale is no longer
left permanently dormant as long as an earlier paid invoice has one.

The replaced `InvRepository::repoClientLatestPaidInvoicequery()` ordered by
`date_created` (editable); its replacement orders by `id` instead — the
immutable, true-insertion-order primary key — which also narrows pitfall
#11 a little further (see below). The old, already-dead
`repoClientInvoiceCountAfterDatequery()` (a leftover of the pre-fix "any
invoice after last payment" rule, zero callers) was deleted at the same
time. `HomeCareCleaningEligibilityServiceTest` gained a dedicated
`searchesBackwardsPastNonServiceInvoiceToFindServiceItem()` case, plus a
`allPaidInvoicesLackServiceItemIsNotEligible()` case covering the
still-dormant path when *no* paid invoice has a Service item at all
(9 tests total, up from 8).

### Not fixed here (documented, not solved)

- Pitfall #6 (permanent, non-expiring QR token) is an accepted tradeoff, not
  a bug — token rotation/expiry would need its own UX decision (how would a
  customer get a fresh sticker?) and hasn't been requested.
- Pitfall #11 (backdated payment dates shifting the anchor) is largely
  moot: nothing in the current eligibility chain reads `Payment.payment_date`
  at all — the "is it paid yet" gate is a pure `status_id` check, and the
  item-template search above now orders by the immutable `id` rather than
  the editable `date_created`. The only remaining, genuinely hypothetical
  exposure is an admin editing `status_id` back and forth on the specific
  invoice a `HomeCareVisit` row already points to — not guarded against, and
  not judged worth an audit trail for what would be a deliberate admin
  action on their own data.

## Required manual step before this works live

Two schema changes need `BUILD_DATABASE=true` set in `.env` on the next
boot (then reverted to `false` immediately after) per the project's
existing Cycle ORM schema-sync convention:

- New `home_care_visit` table.
- New `homecare_auto_invoice_paused` column on the `client` table.

## Verification

Full-project `vendor/bin/psalm --no-cache` — zero errors. Full Testo `Unit`
suite: 597 tests (594 passed, 3 pre-existing/unrelated OpenSSL RSA-key-
generation environment failures). Full PHPUnit suite: 3,875 tests (only the
known Cycle-ORM-mock notices). `HomeCareCleaningEligibilityServiceTest`
rewritten to cover every branch of the new rule (8 tests) (August 2026).

**Pitfall #4 follow-up fix**: full-project `vendor/bin/psalm --no-cache` —
zero errors. Full Testo `Unit` suite: 607 tests (604 passed, the same 3
pre-existing/unrelated failures). Full PHPUnit suite: 3,877 tests (only the
known notices). `HomeCareCleaningEligibilityServiceTest` now 9 tests
(August 2026).
