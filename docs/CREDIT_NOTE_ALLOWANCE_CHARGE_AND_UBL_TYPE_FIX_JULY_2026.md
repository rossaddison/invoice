# Credit Note — Allowance/Charge Propagation & UBL Document Type Fix — July 2026

## Symptom

A credit note needs to stand on its own as a legal/Peppol document (e.g. as
proof submitted to a client or tax authority), but two gaps meant the
generated credit note was incomplete or mislabeled:

1. If the original invoice had line-level (`InvItemAllowanceCharge`) or
   document-level (`InvAllowanceCharge`) discounts/surcharges, the credit
   note's reversed totals were numerically correct but the underlying
   allowance/charge rows that explain those totals were missing from the
   credit note itself.
2. The outbound UBL/Peppol XML for a credit note was generated as an
   `<Invoice>` document with `InvoiceTypeCode` `380`, not the Peppol-required
   `<CreditNote>` document with type code `381` — indistinguishable from a
   normal invoice at the document-type level.

## Root cause

A credit note is just an `Inv` row filed under the "Credit Note Group" (see
`Trait\Credit::processCreditConfirm`) — there is no dedicated entity or flag
for "this is a credit note."

**1. Allowance/charge gap** — `Trait\Credit::processCreditConfirm()` only
called `initializeCreditInvItems()`, `initializeCreditInvAmount()`, and
`initializeCreditInvTaxRate()`. The regular "copy invoice" flow
(`MultipleCopy.php`) already had working code to propagate both
`InvAllowanceCharge` (via `invToInvInvAllowanceCharges()`) and
`InvItemAllowanceCharge` (via `InvItemService::addInvItemAllowanceCharges()`)
onto a copy — the credit-note path never called either.

**2. UBL document type gap** — `PeppolUblXml::xml()` always built an
`App\Invoice\Ubl\Invoice`, never the existing-but-dead-code
`App\Invoice\Ubl\CreditNote` subclass. Worse, `Ubl\Generator::creditNote()`
already existed but delegated to `Generator::invoice()`, which hardcoded the
`'Invoice'` root element name and `Invoice-2` namespace regardless of which
object was actually passed in — so even a direct call to `creditNote()` would
have produced a mislabeled document.

## Fix

**Allowance/charge propagation** (mirrors the existing copy-invoice pattern,
but negated to match the credit note's already-negated items/amounts):

- `InvItemService::addInvItemAllowanceCharges()` gained an optional
  `float $multiplier = 1.0` param (default preserves the existing copy-invoice
  behaviour).
- `InvItemService::initializeCreditInvItems()` now calls it with `-1.0` for
  each item's `InvItemAllowanceCharge` rows.
- `InvAllowanceChargeService::initializeCreditInvAllowanceCharges()` (new) —
  copies the basis invoice's document-level `InvAllowanceCharge` rows with
  negated `amount` (bypasses `InvAllowanceChargeForm`, which enforces
  `amount > 0`, same as the rest of this credit-note path); `vat_or_tax`
  follows automatically since `saveInvAllowanceCharge()` derives it from
  `amount × taxRate%`.
- `Trait\Credit::processCreditConfirm()` now calls the new method alongside
  the existing item/amount/tax-rate initialization.

The sign convention was verified against the existing aggregation logic in
`InvItemService::saveInvItemAmount()` and
`InvAllowanceChargeRepository::getPackHandleShipTotal()` — both already treat
`identifier` (0 = allowance, 1 = charge) as the add/subtract switch and
`amount` as a signed magnitude, so negating `amount` while keeping the same
`identifier` correctly reverses the effect on re-calculation.

**UBL document type**:

- `PeppolHelper::isCreditNote(Inv $invoice)` (new) — resolves the "Credit Note
  Group" id once (memoized) and compares it to the invoice's `group_id`.
  Needed a new `GroupRepository` dependency threaded through
  `InvPeppolCoreDeps` → both `new PeppolHelper(...)` call sites in
  `Trait\Peppol`.
- `Ubl\Generator` — `invoice()`/`creditNote()` refactored into a shared
  `write()` that picks the root element and namespace based on which method
  is called, using the already-defined-but-previously-unused
  `Schema::INVOICE_NS`/`CREDIT_NOTE_NS` constants.
- `PeppolUblXml::xml()` — new `bool $isCreditNote = false` param;
  instantiates `Ubl\CreditNote` vs `Ubl\Invoice` accordingly (same
  constructor, different subclass). `output()` dispatches to
  `Generator::creditNote()` vs `Generator::invoice()` based on the object's
  actual runtime type.
- `PeppolHelperInvoiceLineTrait::buildInvoiceLinesArray()` — line items now
  use `<cac:CreditNoteLine>`/`<cbc:CreditedQuantity>` instead of
  `<cac:InvoiceLine>`/`<cbc:InvoicedQuantity>` when generating for a credit
  note.
- `PeppolHelper::generateInvoicePeppolUblXmlTempFile()` passes
  `$this->isCreditNote($invoice)` through to `PeppolUblXml::xml()`.

## Remaining work (deliberately deferred)

This app stores a credit note's `InvItem.quantity`, `InvItemAmount` totals,
and `InvAmount` totals as **negative** numbers (for internal netting against
the original invoice). Those negative values currently flow straight into
the UBL `CreditedQuantity` / `LineExtensionAmount` / `LegalMonetaryTotal`
fields unchanged.

Most real-world UBL/Peppol `CreditNote` implementations expect these as
**positive** magnitudes — the document type code (`381`) and root element
alone signal "this reduces the total," not a second negative sign on top.
Converting to `abs()` at the XML-generation boundary only (not touching the
stored DB values, which need to stay negative for internal netting) is the
planned next pass — it touches many numeric fields across
`generateInvoicePeppolUblXmlTempFile()` (line amounts, tax amounts,
`LegalMonetaryTotal`, document-level allowance/charges) so it's being done as
its own reviewed change rather than folded into this one.

## Verification

- `vendor/bin/psalm --no-cache` clean on all changed files.
- New tests:
  - `Tests/Unit/Invoice/InvAllowanceCharge/InvAllowanceChargeServiceTest.php`
  - `Tests/Unit/Invoice/Service/InvItemServiceCreditTest.php`
  - `Tests/Unit/Invoice/Libraries/PeppolUblXmlCreditNoteTest.php` — builds a
    real minimal UBL document graph and runs it through the actual
    `xml()`/`output()`/`Generator` pipeline (no mocking of the code under
    test), confirming a normal invoice emits `<Invoice
    xmlns=".../Invoice-2">` with `InvoiceTypeCode 380`, and a credit note
    emits `<CreditNote xmlns=".../CreditNote-2">` with `InvoiceTypeCode 381`.
- Full Peppol-related test suite (288 tests) and the pre-existing
  `InvAllowanceCharge`/`InvItem` service tests pass with no regressions.
- Sign convention (allowance/charge negation) additionally checked against
  real data in the dev DB (invoice `INV286`, id 289, which has both an
  invoice-level charge and two item-level allowance/charges).
