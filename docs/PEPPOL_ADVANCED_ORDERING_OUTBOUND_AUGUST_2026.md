# Peppol BIS Advanced Ordering — Outbound OrderResponseAdvanced (August 2026)

Phase 2, seller-side response to an inbound `Order` (see
`docs/PEPPOL_ADVANCED_ORDERING_INBOUND_AUGUST_2026.md`): staff can
acknowledge a whole `SalesOrder` or decide each `SalesOrderItem` line
independently, with the header `OrderResponseCode` always derived from
line decisions rather than picked directly.

## Service

`OrderResponseAdvancedService`:

- `send()`/`previewXml()` — whole-order Acknowledge shortcut.
- `sendPerLine()`/`previewPerLineXml()` — real per-line review, header
  code derived via `OrderResponseCode::deriveFromLineStatusCodes()`
  rather than staff picking the header code directly, so the header
  can't contradict what was actually decided per line.

## New UBL model

`OrderResponse`, `OrderResponseLine`, `OrderResponseCode`,
`OrderResponseLineStatusCode`; `Generator::orderResponse()` rendering.
`As4Constants`/`Schema` gain the Advanced Ordering process/document type
identifiers for `OrderResponseAdvanced` (the T116 doctype id constructed
by the same `busdox-docid-qns` pattern as elsewhere in the file, **not**
independently confirmed from a live docs.peppol.eu page — verify against
a real SMP lookup before production use).

## Staff UI

`SalesOrderController` gains send/preview actions and routes, both
whole-order and per-line. Two new modals on `salesorder/view.php`:

- `modal_acknowledge_order_response.php` — whole-order AB shortcut.
- `modal_send_order_response_per_line.php` — per-line decision table.

Both include a no-JS preview-in-new-tab button, matching this app's
existing UBL-preview convention elsewhere.

## Persisted fields

`SalesOrder`/`SalesOrderItem` gain persisted Peppol response code
fields, so a sent decision survives past the request that sent it and
can be shown back on the view.

## Verified

13 new Testo tests (send/sendPerLine/preview paths, header-code
derivation, per-item persistence, dispatch-failure non-persistence) plus
`OrderResponse`/`OrderResponseCode` PHPUnit tests. Full Testo Unit suite:
956 passed. Psalm `--no-cache` on all new/changed files: no errors
found. Real SMP/AP delivery against a live Peppol network not yet
verified.
