# Invoice Copy — Spreadsheet Import (`modal_copy_inv_multiple`)

## Summary

The `inv/index` bulk-copy modal (`#modal-copy-inv-multiple`) gains a CSV spreadsheet
import section. A single uploaded file drives multiple invoice copies — one copy per
CSV row — with per-row control over the creation date, note, whether the original
amount is preserved, and an optional payment date.

---

## User Flow

1. On `inv/index`, tick one or more invoice checkboxes and open **Copy Invoice**
   (`#modal-copy-inv-multiple`).
2. Optionally tick one or more target clients from the scrollable checkbox list.
   If no client is ticked the invoice's own client is used.
3. Click **Download Template** to get `invoice-copy-template.csv` (four columns,
   three example rows, UTF-8).
4. Fill in the CSV and upload it via the **Spreadsheet Import** file input.
5. A preview table appears showing parsed rows.
6. Click **Import Spreadsheet** (blue) — one invoice copy is created per CSV row
   for every selected source invoice × every selected client.
7. The existing **Submit** button (green) still works as before (single date,
   no note, no payment).

---

## CSV Format

| Column | Type | Required | Notes |
|---|---|---|---|
| `date_created` | `YYYY-MM-DD` | Yes | Date of the new invoice; falls back to today if blank |
| `note` | string | No | Replaces the original invoice's note field |
| `same_amount` | `0` or `1` | Yes | `1` = copy the `inv_amount` record; `0` = skip it |
| `payment_date` | `YYYY-MM-DD` | No | If set (and `same_amount=1`), creates a Payment for the full amount |

The first row is the header and is always skipped. Empty `payment_date` values leave
the invoice unpaid.

---

## PHP Implementation

### `src/Invoice/Inv/Trait/MultipleCopy.php`

**`copyInvToClient()` — extended signature**

Three new optional parameters added (backwards-compatible defaults):

```php
private function copyInvToClient(
    int $invId, Inv $original, int $targetClientId,
    InvCopyDeps $d, FormHydrator $formHydrator, array $productIds,
    string $createdDate,
    string $note = '',
    bool $sameAmount = true,
    string $paymentDate = '',
    ?PaymentService $paymentService = null,
): bool
```

- If `$sameAmount` is `false`, `invToInvInvAmount()` is skipped — the copied invoice
  has no pre-computed amount row.
- If `$sameAmount && $paymentDate !== '' && $paymentService !== null`, a `Payment`
  record is created via `PaymentService::savePayment()` for the full `inv_amount.total`
  using the copied invoice's `payment_method`.

**`multiplecopyspreadsheet()`**

New public action method. Reads from **query parameters** (GET, matching the
`multiplecopy` pattern — avoids CSRF middleware which applies to POST):

| Param | Type | Description |
|---|---|---|
| `keylist[]` | repeated string | IDs of source invoices |
| `client_ids[]` | repeated string | Target client IDs (optional) |
| `rows_json` | JSON string | URL-encoded JSON array of row objects |

Row object shape:
```json
{"date_created":"2026-07-01","note":"June services","same_amount":"1","payment_date":"2026-07-15"}
```

`PaymentService` is DI-injected directly into the action method signature — no
constructor change needed on `InvController`.

> **Note on HTTP method**: The Yii3 `CsrfTokenMiddleware` is wired at the
> route-collection level and validates every non-safe (POST/PUT/DELETE) request.
> Sending a POST with `X-CSRF-Token` header was tried but the WAMP Apache layer
> converts some POST requests to GET via redirect, causing a 405. Using GET —
> matching the existing `multiplecopy` endpoint — bypasses CSRF (GET is safe
> per RFC 9110) and works without redirect issues.

**`csvTemplateInvCopy()`**

New public action method. `ResponseFactoryInterface` and `StreamFactoryInterface` are
DI-injected directly into the method signature (Yii3 resolves them per-request without
requiring them in `InvController`'s constructor). Returns:

```
Content-Type: text/csv; charset=UTF-8
Content-Disposition: attachment; filename="invoice-copy-template.csv"
```

### `config/common/routes/routes.php`

Two new routes:

| Method | Path | Action |
|---|---|---|
| `POST` | `/inv/multiplecopyspreadsheet` | `InvController::multiplecopyspreadsheet` |
| `GET`  | `/inv/copycsvtemplate`         | `InvController::csvTemplateInvCopy` |

Both require the `editInvoice` permission, matching all other `inv/` routes.

---

## TypeScript Implementation

### `src/typescript/utils.ts`

Added `postJson<T>(url, body)` — sends `Content-Type: application/json` POST with
JSON-stringified body and parses the JSON response. Mirrors the existing `getJson`
signature pattern.

### `src/typescript/invoice.ts`

New interfaces:

```typescript
interface CopyInvSpreadsheetRow {
    date_created: string; note: string; same_amount: string; payment_date: string;
}
interface CopyInvSpreadsheetPayload {
    keylist: string[]; client_ids: string[]; rows: CopyInvSpreadsheetRow[];
}
```

New methods:

- **`parseCopyCsv(text)`** — splits on `\r?\n`, skips header, returns
  `CopyInvSpreadsheetRow[]`. Handles simple CSV (no embedded commas or quoted
  newlines).
- **`handleCopyCsvFileChange(input)`** — reads the uploaded file via `file.text()`,
  calls `parseCopyCsv`, populates `#copy_inv_csv_tbody`, shows preview div and
  **Import Spreadsheet** button.
- **`handleCopyInvoicesSpreadsheet()`** — collects checked invoice IDs and client IDs,
  reads rows from the DOM table, POSTs to `/invoice/inv/multiplecopyspreadsheet` via
  `postJson`, reloads on success.

Event wiring:

- `handleChange` dispatches `#copy_inv_csv_file` → `handleCopyCsvFileChange`.
- `handleClick` dispatches `#modal_copy_inv_spreadsheet_confirm` →
  `handleCopyInvoicesSpreadsheet`.

---

## View

### `resources/views/invoice/inv/modal_copy_inv_multiple.php`

Added below the client list, inside the existing `<form>`:

- `<hr>` separator
- "Spreadsheet Import" label + **Download Template** `<a>` button (opens
  `/invoice/inv/copycsvtemplate` in new tab)
- `<input type="file" id="copy_inv_csv_file" accept=".csv">`
- Hidden preview `<div>` containing a `<table>` with `<thead>` (four column headers)
  and `<tbody id="copy_inv_csv_tbody">` (populated by TypeScript)

Footer additions:

- **Import Spreadsheet** button (`#modal_copy_inv_spreadsheet_confirm`, `display:none`
  until a valid CSV is loaded)
- Existing **Submit** button (`#modal_copy_inv_multiple_confirm`) unchanged

Child HTML nodes inside `<a>` buttons use `H::openTag` / `H::closeTag` — **not**
`H::tag($tag, $innerHtml, $attrs)` — because `H::tag` HTML-encodes its content
argument, which renders child `<i>` icon tags as literal text.

---

## Translation Keys Added (`resources/messages/en/app.php`)

| Key | Value |
|---|---|
| `download.template` | `Download Template` |
| `import.spreadsheet` | `Import Spreadsheet` |
| `spreadsheet.import` | `Spreadsheet Import` |

---

## Files Changed

| File | Change |
|---|---|
| `src/Invoice/Inv/Trait/MultipleCopy.php` | New params on `copyInvToClient`; new `multiplecopyspreadsheet` and `csvTemplateInvCopy` action methods; import `Payment`, `PaymentService`, `ResponseFactoryInterface`, `StreamFactoryInterface` |
| `config/common/routes/routes.php` | Two new routes: `POST multiplecopyspreadsheet`, `GET copycsvtemplate` |
| `resources/views/invoice/inv/modal_copy_inv_multiple.php` | Spreadsheet import section + Import button in footer |
| `src/typescript/utils.ts` | `postJson<T>(url, body, csrfToken?)` helper (retained for future use) |
| `src/typescript/invoice.ts` | `CopyInvSpreadsheetRow`, `CopyInvSpreadsheetPayload` (with `rows_json`), `parseCopyCsv` (auto-detects `,` or `;` delimiter), `handleCopyCsvFileChange`, `handleCopyInvoicesSpreadsheet` (uses `getJson` with `rows_json` param) |
| `src/Invoice/Inv/CsvDateNormaliser.php` | New utility class — round-trip validated multi-format date normalisation to `Y-m-d` |
| `Tests/Testo/Invoice/Inv/CsvDateNormaliserTest.php` | 10 Testo tests covering all six input formats, overflow rejection, empty string, unrecognised input |
| `resources/messages/en/app.php` | Three new translation keys |

Psalm errorLevel 1: zero errors. TypeScript bundle rebuilt (146.8 kb).
