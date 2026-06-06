# MTD VAT — Purchase Entries & Bridging Software Strategy

**Date:** June 2026  
**Status:** Implemented — `PurchaseEntry` entity, CRUD controller, CSV import, and VAT100 Box 4/7 wiring complete

---

## Overview

Yii3-i targets full **Making Tax Digital (MTD) for VAT** compliance by April 2029
(UK e-invoicing mandate). The submission pipeline covers outbound sales invoices
natively. This document records the decided strategy for the **purchase-side** of
the VAT return (Boxes 4 and 7) and the use of HMRC's Developer Hub sandbox for
end-to-end testing.

---

## What is already built

| Component | Location | Description |
|-----------|----------|-------------|
| FPH capture UI | `partial_settings_making_tax_digital.php` | All 16 `WEB_APP_VIA_SERVER` headers |
| FPH generation | `SettingController::fphgenerate()` | UUID device/user IDs via JS |
| FPH header assembly | `HmrcController::getWebAppViaServerHeaders()` | Assembles all 16 headers from settings |
| FPH validation | `HmrcController::fphValidate()` | Hits HMRC test-api FPH validator |
| FPH feedback by API | `HmrcController::fphFeedback(api)` | Hits HMRC test-api feedback endpoint |
| VAT obligations | `HmrcController::vatObligations()` | `GET /organisations/vat/{vrn}/obligations` |
| VAT return prepare | `HmrcController::vatReturnPrepare()` | Pre-fills Boxes 1 & 6 from `InvAmount` |
| VAT return submit | `HmrcController::vatReturnSubmit()` | `POST /organisations/vat/{vrn}/returns` |
| VAT result view | `vatReturnResult.php` | Shows `formBundleNumber`, payment indicator |
| HMRC OAuth2 client | `config/web/di/yii-auth-client.php` | Developer Sandbox HMRC OAuth2 flow |
| VRN setting | `partial_settings_making_tax_digital.php` | `vat_registration_number` field |

---

## VAT100 field sourcing

| Box | Description | Source |
|-----|-------------|--------|
| 1 | Output VAT | `SUM(inv_amount.tax_total)` for sent/viewed/paid invoices in period — **auto** |
| 2 | EC acquisition VAT | Post-Brexit: typically 0 — **manual** |
| 3 | Total VAT due | Box 1 + Box 2 — **auto-calculated in browser** |
| 4 | Input VAT reclaimed | `SUM(purchase_entry.vat_amount)` for period — **from `PurchaseEntry`** |
| 5 | Net VAT | \|Box 3 − Box 4\| — **auto-calculated in browser** |
| 6 | Sales ex-VAT | `SUM(inv_amount.item_subtotal)` for sent/viewed/paid invoices in period — **auto** |
| 7 | Purchases ex-VAT | `SUM(purchase_entry.amount_ex_vat)` for period — **from `PurchaseEntry`** |
| 8 | EC goods supplied ex-VAT | Post-Brexit: typically 0 — **manual** |
| 9 | EC acquisitions ex-VAT | Post-Brexit: typically 0 — **manual** |

---

## Why not `inv_type = 'purchase'` on `Inv`

The obvious approach — adding an `inv_type` flag to the existing `Inv` entity so
purchase invoices reuse the full invoice infrastructure — was considered and
rejected for these reasons:

1. **COGS ≠ Box 7.** Box 7 is the value of purchase invoices *received* from
   suppliers in the period, not the cost of goods *sold*. A COGS approach requires
   a stock-taking system (opening stock + purchases − closing stock) which is out of
   scope. Box 7 only needs simple aggregation of supplier invoice totals.

2. **Overkill.** Purchase invoices for MTD VAT only need four fields: date, supplier,
   amount ex-VAT, VAT amount. Forcing them through the full `Inv` → `InvItem` →
   `InvAmount` → `InvTaxRate` pipeline adds unnecessary complexity.

3. **Guard risk.** Existing `InvRepository` queries, Peppol exports, dashboard
   counts, and all reporting would need immediate guarding to exclude purchase
   records. Any missed guard is a data leak.

---

## The `PurchaseEntry` approach

A **lightweight standalone entity** with no relation to `Inv`:

```php
// src/Infrastructure/Persistence/PurchaseEntry/PurchaseEntry.php
#[Entity]
class PurchaseEntry
{
    id            int (primary)
    date          date           — invoice date (must fall within VAT period)
    supplier      string(200)    — supplier name (free text)
    description   string(500)    — optional reference / description
    amount_ex_vat decimal(20,2)  — net amount, feeds Box 7
    vat_amount    decimal(20,2)  — VAT charged, feeds Box 4
    created_at    datetime       — audit trail
}
```

No item lines. No `InvAmount`. No client relation. No status lifecycle.

---

## Two input paths (bridging software strategy)

### Path A — Manual row entry

A simple CRUD form at `/purchase-entry` lets users add supplier invoice records
one at a time as they arrive. Suitable for low-volume businesses.

### Path B — CSV import (bridging software)

HMRC's [bridging software](https://www.gov.uk/guidance/making-tax-digital-for-vat-as-a-developer)
concept: the user maintains purchase records in a spreadsheet, exports as CSV,
and uploads to Yii3-i. The import endpoint parses the CSV and bulk-inserts
`PurchaseEntry` rows for the period.

**CSV format (four required columns, header row):**

```
date,supplier,amount_ex_vat,vat_amount
2026-01-05,Office Supplies Ltd,120.00,24.00
2026-01-12,Cloud Hosting Co,200.00,40.00
```

Optional fifth column: `description`

---

## Developer Sandbox testing (HMRC Developer Hub)

Routes already wired for sandbox testing:

| Route | Action | Purpose |
|-------|--------|---------|
| `GET /backend/hmrc/fphValidate` | `HmrcController::fphValidate()` | Validate all 16 FPH headers against HMRC test API |
| `GET backend/hmrc/fphFeedback/vat` | `HmrcController::fphFeedback('vat')` | Per-API FPH feedback |
| `GET /backend/hmrc/createTestUserIndividual` | `HmrcController::createTestUserIndividual()` | Create a sandboxed test user |
| `GET /backend/hmrc/vatObligations` | `HmrcController::vatObligations()` | Retrieve open obligations (production API) |
| `GET /backend/hmrc/vatReturnPrepare` | `HmrcController::vatReturnPrepare()` | Pre-filled VAT100 form |
| `POST /backend/hmrc/vatReturnSubmit` | `HmrcController::vatReturnSubmit()` | Submit return (production API) |

All test routes hit `https://test-api.service.hmrc.gov.uk`. Submission routes hit
`https://api.service.hmrc.gov.uk`. Switch is via the URL — no env flag needed.

---

## Next steps

- [x] `PurchaseEntry` Cycle ORM entity — `src/Infrastructure/Persistence/PurchaseEntry/PurchaseEntry.php`
- [x] `PurchaseEntryRepository` with `repoVatTotalsForPeriod(string $start, string $end): array`
- [x] `PurchaseEntryController` — CRUD + CSV import endpoint
- [x] Wire Box 4 and Box 7 into `HmrcController::vatReturnPrepare()`
- [x] `vatReturnPrepare.php` view — Box 4 and Box 7 pre-filled from `PurchaseEntry` totals
- [x] PHPUnit entity test (16 assertions, Psalm errorLevel 1 clean)
- [x] Run `BUILD_DATABASE=true` to create the `purchase_entry` table
- [ ] Integration test for `PurchaseEntryRepository::repoVatTotalsForPeriod()`

---

## Related docs

- [Future Peppol UK](FUTURE_PEPPOL_UK.md) — April 2029 mandate, Peppol BIS Billing 3.0, HMRC MTD pipeline
- [UK e-invoicing B2B/B2G 2029](UK-E-INVOICING-MANDATE.md) — mandate scope and timeline
- [Fraud Prevention Headers Bugfix](FPH_BUTTON_EVENT_BINDING_BUG_REPORT.md) — FPH button event binding history
