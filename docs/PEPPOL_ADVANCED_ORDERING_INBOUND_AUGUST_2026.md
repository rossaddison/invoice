# Peppol BIS Advanced Ordering — Inbound Order → SalesOrder (August 2026)

Phase 1 of Peppol BIS Advanced Ordering 3.0 support: a real UBL `Order`
document received over AS4 is now parsed and turned into a `SalesOrder` +
`SalesOrderItem`s. This app only ever plays **Seller** for Ordering — it
never issues `Order`/`OrderChange`/`OrderCancellation` itself, only ever
sends `OrderResponseAdvanced` back (Phase 2, see
`docs/PEPPOL_ADVANCED_ORDERING_OUTBOUND_AUGUST_2026.md`).

## New parsing/import pipeline

- `UblOrderData`/`UblOrderLineData` DTOs, `UblOrderXmlParser` (Buyer
  party, `cac:OrderLine`/`cac:LineItem` structure — verified against the
  real Peppol docs), `As4OrderImportService`
  (`As4PayloadHandlerInterface`).
- `As4PayloadRouter` is new too: `As4UserMessageHandlerService` only ever
  held one handler before (always Invoice); it now routes by the
  payload's root element to the existing Invoice importer or the new
  Order one.
- `Schema::ORDER_NS` added alongside the existing UBL namespace
  constants.
- Extracted `UblXmlHelperTrait` and `As4PartyIdSplitTrait` up front to
  avoid duplicating the small XPath/party-id-splitting helpers across
  both parsers/importers.

## Two pre-existing bugs found and fixed, neither caused by this change

- `SalesOrder.quote_id` had a genuine `NOT NULL` FK to `quote.id` —
  every `SalesOrder` was required to descend from a staff-authored
  `Quote`, which an inbound `Order` never has. Fixed with a real
  migration (Cycle's `BUILD_DATABASE` schema sync only adds columns, it
  doesn't relax an existing one's nullability — had to hand-write the
  `ALTER TABLE`). Cycle relation changed to `nullable: true`; all 5
  pre-existing rows' `quote_id` values untouched.
- `ClientPeppolRepositoryInterface` had no DI binding anywhere in the
  codebase, meaning `As4InvoiceImportService` (which also depends on it)
  was likely never actually resolvable through the container in any
  context. Added the missing binding in `config/common/di/as4.php`.

## New fields

`SalesOrder.peppol_order_response_code` (nullable) tracks the eventual
AB/AP/RE/CA `OrderResponseAdvanced` status — deliberately orthogonal to
the pre-existing `status_id`/`SalesOrderStatusTrait`, which has no state
meaning "awaiting a Peppol response". `SalesOrderItem`'s existing
`peppol_po_itemid`/`peppol_po_lineid` fields (already present from the
Invoice-side pattern) are reused for the buyer's own line id. Every
imported line gets `TaxRateRepository::repoFirstByIdQuery()`'s rate as a
placeholder, since a Peppol Order line carries no tax information at all
(confirmed against the real element tree).

## Verified

Live-verified end-to-end with a throwaway console command (written, run
once against the real DB, then deleted) using a real seeded
`client_peppol` row: correct `SalesOrder` (client resolved, `quote_id`
`NULL` confirming the FK fix, buyer's Order number captured) and
`SalesOrderItem` (tax rate resolved, buyer's line id preserved). Test
rows cleaned up after. Also fixed 3 unrelated pre-existing PHPUnit
notices found while re-running the full suite (stub-without-expectations
in `StripeWebhookHandlerTest`/`AdyenWebhookHandlerTest`/
`GoCardlessWebhookHandlerTest`) via
`#[AllowMockObjectsWithoutExpectations]`, per the project's standing
convention for that notice class. Full-project Psalm clean, PHPUnit
3936/3936 (0 notices), Testo 943/943.

Not yet built: `OrderResponseAdvanced` generation/sending (see Phase 2),
inbound `OrderChange`/`OrderCancellation`, any staff UI to action an
imported `SalesOrder`.
