# HomeCare Run-Sheet Paper Signoff + AI-Vision Reconciliation

## What This Feature Does

Reconciles a HomeCare "run" (today's date + `category_secondary`, the same filter
`HomeCareRunContext` already drives on `inv/index`) via a paper backup channel:

1. **Export** (`GET /homecarerunsheet`, `POST /homecarerunsheet/export`) — snapshots the
   run's invoices into a `HomeCareRunSheet` + one `HomeCareRunSheetItem` per invoice, sorted
   in route order by `Dwelling::house_number_numeric`, and writes a printable CSV to disk
   (columns: seq, invoice id, client, house number, assigned worker, plus blank columns for
   the field to fill in by hand, and a `DoNotSendReason` legend).
2. Printed and hand-annotated in the field — the worker who actually did the job, whether it
   was completed, and a coded do-not-send reason if not.
3. **Scan** (`POST /homecarerunsheet/upload/{id}`) — the annotated sheet is
   photographed/scanned back in and stored under
   `src/Invoice/Uploads/HomeCare_run_sheets/`; Claude Haiku 4.5 reads the handwriting via a
   structured-output vision call (a closed-set schema enumerating the real invoice ids,
   active worker ids, and `DoNotSendReason` codes on that sheet) and writes the detected
   worker/completion/reason onto each `HomeCareRunSheetItem`.
4. **Review** (`GET /homecarerunsheet/review/{id}`) — a staging screen shows only the rows
   the vision read actually changed from what was printed
   (`HomeCareRunSheetItem::hasDetectedChange()`), so the office reviews adjustments, not the
   whole run.
5. **Apply** (`POST /homecarerunsheet/apply/{id}`) — accepted adjustments are written back
   onto `Inv` (worker reassignment via `setWorker()`, `do_not_send` — set only, never
   cleared, by design), and the whole run is batch-flipped to "sent" via the new
   `InvMarkSentService`, which delegates the actual status transition to the existing
   `SettingRepository::invoiceMarkSent()` and additionally writes an `InvSentLog` entry per
   invoice.

Settings → HomeCare has a "Run Sheet Vision Scan" card holding the Anthropic API key (a
password-type field, with a "Get API Key ↗" link to
`https://console.anthropic.com/settings/keys`), and "Run Sheets" is in the Invoice nav
dropdown alongside "Workers". The Recent Run Sheets index shows category and item-count per
batch.

`HomeCareRunSheet`'s lifecycle is `Exported → Scanned → PendingReview → Applied`
(`HomeCareRunSheetStatus`); a batch that fails partway through a scan stays at `Scanned` and
can be retried from the Review screen rather than being stuck.

---

## Live Verification Status

Export and Review are live-verified end-to-end against real invoices and a real `Dwelling`
row on local MySQL/WAMP: correct `HomeCareRunSheet`/`HomeCareRunSheetItem` rows, correct CSV
on disk with `house_no` correctly resolved through `client.dwelling_id`, and the Review
screen rendering correctly at every status. The vision call itself is confirmed to reach and
authenticate against the Anthropic API with the configured key — the only thing currently
blocking a full end-to-end run is account credit, not a code issue. A full live Apply run
(a batch actually reaching `PendingReview` with real detected changes) hasn't happened yet
for the same reason.

---

## Test Coverage

All 7 classes in the feature have Testo unit test coverage — 40 tests, added in this
session:

| Test file | Covers |
|---|---|
| `Tests/Testo/Infrastructure/Persistence/HomeCareRunSheet/HomeCareRunSheetTest.php` | Entity lifecycle — `markScanned`/`markPendingReview`/`markApplied`, `reqId()` guards |
| `.../HomeCareRunSheetItem/HomeCareRunSheetItemTest.php` | `hasDetectedChange()` — the shared predicate the staging query and Apply step both key off, gated on `detected_completed` rather than `detected_worker_id` so an illegible-handwriting-but-marked-incomplete row still counts as changed |
| `Tests/Testo/Invoice/HomeCareRunSheet/HomeCareRunSheetApplyServiceTest.php` | Refuses a non-`PendingReview` batch; worker reassignment; `do_not_send` set-never-clear rule; a rejected row left untouched but still included in the mark-sent pass; invoice-not-found skip |
| `.../HomeCareRunSheetVisionServiceTest.php` | The two guard clauses before the real `Anthropic\Client` call — empty items, missing API key |
| `.../HomeCareRunSheetExportServiceTest.php` | Idempotent re-fetch of an already-open batch; route-order sort with no-dwelling invoices sorting last; full CSV content verified byte-for-byte off real temp-dir file I/O |
| `.../HomeCareRunSheetScanServiceTest.php` | Scan file written to disk with the right extension per `MediaType` (jpg/png/gif/webp); `Scanned → PendingReview` transition order |
| `Tests/Testo/Invoice/Inv/InvMarkSentServiceTest.php` | Draft/blocked/not-found guards, `InvSentLog` write, per-invoice independence |

Conventions: real `new Inv()`/`new Worker()`/`new HomeCareRunSheet*()` value objects rather
than mocking entities, Mockery-mocking only repository/service collaborators, and real
temp-dir file I/O for the two file-writing services (`HomeCareRunSheetExportServiceTest`,
`HomeCareRunSheetScanServiceTest` — mirrors `UploadServiceTest`'s `deleteUpload` convention).

`vendor/bin/psalm --no-cache` on all 7 new files: 0 errors. Full project Testo suite:
1048/1048 passing.

---

## Design Notes

Existing app patterns reused as-is: `HomeCareRunContext`, `inv/setworker`'s
worker-reassignment pattern, the `DoNotSendReason` enum, `Inv::do_not_send`/
`blocksSending()`, `Dwelling::house_number_numeric`, the `fputcsv`/`php://temp` CSV
convention, the `Form()` raw-HTML wrapper, and
`CategorySecondaryRepository::optionsDataCategorySecondaries()`. The scanned sheet is stored
as a bare filename string against the batch (`CompanyPrivate::logo_filename` convention)
rather than through the `Upload` entity, since `Upload` is client-scoped and doesn't fit a
document spanning one run's many clients.

New dependency: `anthropic-ai/sdk:^0.44.0`. Model: Claude Haiku 4.5, chosen for a bounded
closed-set extraction task with structured JSON-schema output.

---

## Files Changed (This Session)

| File | Change |
|---|---|
| `config/common/di/homecare.php` | Repository interface bindings |
| `resources/views/invoice/setting/views/partial_settings_homecare.php` | Vision API key field |
| `resources/views/invoice/setting/views/partial_settings_two_factor_authentication.php` | 2FA compulsory (see below) |
| `resources/views/invoice/homecarerunsheet/index.php`, `review.php` | Review screen states |
| `resources/views/layout/invoice.php` | "Run Sheets" nav entry |
| `src/Invoice/Setting/Trait/SettingFileFolderTrait.php`, `SettingStaticPathsTrait.php`, `SettingTooltipTrait.php` | Vision key setting plumbing/tooltip |
| `src/Invoice/Trait/InvoiceInstallTrait.php` | Default settings for fresh installs |
| `src/Invoice/Inv/InvMarkSentService.php` | New — the "mark as sent" half of the Apply step |
| `resources/messages/en/app.php` | New translation keys |
| 7 new `Tests/Testo/**` files | See table above |

---

## Unrelated Change Made in the Same Session

Settings → TFA's `enable_tfa` checkbox is now permanently checked and disabled (decorative
only), and always submits `1` — two-factor authentication is compulsory for every account
rather than optional.

---

## Verification

Full Testo Unit suite: 1048/1048 passing. Full-project `vendor/bin/psalm --no-cache`: 0
errors on every new/changed file. Export, Review, and the vision-call auth path are
live-verified against real MySQL/WAMP; the vision extraction itself and a full live Apply
run are pending Anthropic account credit.
