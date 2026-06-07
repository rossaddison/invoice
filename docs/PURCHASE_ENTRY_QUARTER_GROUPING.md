# Purchase Entry — VAT Quarter Grouping, Locale Defaults & Index UI

**Date:** June 2026
**Branch:** `purchase-entry-testing`
**Status:** Implemented — Psalm clean, no suppressions, no SonarQube S1131/S1192/S3358 violations

---

## Overview

This document covers the second phase of the Purchase Entry feature built on top of the
entity/CRUD/CSV foundation described in [MTD_VAT_PURCHASE_ENTRIES.md](MTD_VAT_PURCHASE_ENTRIES.md).
The focus here is the `purchaseentry/index` UI, VAT quarter grouping driven by the
existing tax year start date settings, and a locale-defaults page that lets users
apply the correct start date for their country with one click.

---

## Index View — What Was Built

### GridView + HTMX partial swap

`resources/views/invoice/purchaseentry/index.php` uses the same pattern as
`inv/index.php`:

- **`OffsetPaginator`** on the `EntityReader` from `PurchaseEntryRepository::getReader()`
- **`GridView`** with `DataColumn` / `ActionColumn` / `ActionButton`
- **HTMX wrapper div** (`#purchase-entry-list`) with `hx-boost="true"`,
  `hx-target`, `hx-swap="outerHTML"`, `hx-select` — pagination and group-toggle
  links swap only the list section without a full page reload
- Add Entry and CSV Import buttons carry `hx-boost="false"` so they bypass the
  HTMX intercept and trigger a normal full-page navigation to the form

### Group-by toggle

Three buttons (All / By Month / By Supplier) are always visible. A fourth
**By Quarter** button is always rendered but is **disabled** (Bootstrap `.disabled`
class + `aria-disabled="true"` + `tabindex="-1"`) when the tax year start date
settings are not fully configured.

The controller `#[Query('groupBy')]` parameter is validated against an allowlist:
- `['month', 'supplier', 'quarter']` when tax year is set
- `['month', 'supplier']` when not set — prevents manual URL manipulation from
  triggering an unconfigured quarter grouping

Grouped views (month, supplier, quarter) bypass the paginator and fetch all
entries via `iterator_to_array()`, grouping in PHP with `ksort()`.

---

## VAT Quarter Grouping

### Driven by existing settings

Three settings already used by `DateHelper::taxYearToImmutable()` and the
`SettingController` taxes tab determine the VAT quarter boundaries:

| Setting key | Example value | Description |
|---|---|---|
| `this_tax_year_from_date_year` | `2025` | Tax year start year |
| `this_tax_year_from_date_month` | `04` | Tax year start month (zero-padded) |
| `this_tax_year_from_date_day` | `06` | Tax year start day (zero-padded) |

### Quarter calculation

The quarter key function in `index.php`:

```php
$vatQuarterKey = static function (DateTimeImmutable $date) use ($startMonth): string {
    $m       = (int) $date->format('n');
    $y       = (int) $date->format('Y');
    $quarter = (int) floor((($m - $startMonth + 12) % 12) / 3) + 1;
    $taxYear = $m < $startMonth ? $y - 1 : $y;
    return sprintf('Q%d %d/%d', $quarter, $taxYear, $taxYear + 1);
};
```

For a UK tax year starting April (month 4):

| Entry month | Quarter label |
|---|---|
| April, May, June | Q1 YYYY/YYYY+1 |
| July, August, September | Q2 YYYY/YYYY+1 |
| October, November, December | Q3 YYYY/YYYY+1 |
| January, February, March | Q4 YYYY-1/YYYY |

Labels sort correctly with `ksort()` (e.g. `Q1 2025/2026 < Q2 2025/2026 < Q1 2026/2027`).

Each quarterly group shows subtotals for **Amount ex-VAT** and **VAT Amount** in the
section header row, mirroring the month and supplier group layouts.

---

## Breadcrumbs — Settings Navigation

Following the pattern of `inv/index.php`, three `BreadcrumbLink::to()` entries appear
above the grid. Each links directly to the taxes tab with a hash anchor targeting the
specific field:

```php
$urlGenerator->generate(
    'setting/tabIndex',
    [],
    ['active' => 'taxes'],
    'settings[this_tax_year_from_date_year]',  // → #settings[this_tax_year_from_date_year]
)
```

The tooltip (`data-bs-toggle="tooltip"`) shows the current setting value, or **⏳**
when the value is empty — giving users an at-a-glance view of which fields need
attention without leaving the index page.

A **📋 Locale defaults** button sits inline with the breadcrumbs and navigates to
the locale-defaults page (with `hx-boost="false"`).

---

## Flash Message

When any of the three tax year settings are empty, `PurchaseEntryController::index()`
emits a warning flash message **before** `$this->alert()` is resolved:

```php
if (!$taxYearSet) {
    $this->flashMessage('warning',
        $this->translator->translate('purchase.entry.tax.year.not.configured'));
}
```

Translation key (`resources/messages/en/app.php`):

```
'purchase.entry.tax.year.not.configured' =>
    'VAT quarter grouping is unavailable: Tax Year Start (year, month, day) is not
     fully configured. Use the breadcrumb links below to set it.'
```

---

## Locale Defaults Page

**Route:** `GET /entry/tax-year-locales` → `entry/tax-year-locales`
**View:** `resources/views/invoice/purchaseentry/tax_year_locales.php`

A two-section table (non-calendar-year countries first, then calendar-year) covering
~50 locales:

| Country | Tax Year Start |
|---|---|
| United Kingdom | 6 April |
| Australia | 1 July |
| New Zealand / India / Japan / Singapore / Hong Kong | 1 April |
| South Africa | 1 March |
| Bangladesh / Pakistan / Egypt / Kenya | 1 July |
| Myanmar / Thailand (govt) | 1 October |
| US, Canada, most of Europe, China, Brazil … | 1 January |

Each row has an **Apply** button that POSTs `month` and `day` to
`POST /entry/apply-tax-year-locale`. The controller action:

1. Validates month (`01`–`12`) and day (`01`–`31`) with `preg_match`
2. Preserves the existing `this_tax_year_from_date_year` value if already set;
   otherwise defaults to the current calendar year
3. Saves all three settings via `$this->sR->withKey($key)` → `setSettingValue()` →
   `$this->sR->save()`
4. Flashes a success message and redirects to `entry/index`

Translation keys added:

```
'purchase.entry.tax.year.locale.applied' =>
    'Tax year start date applied. You can now group purchase entries by VAT quarter.'
'purchase.entry.tax.year.locale.invalid' =>
    'Invalid month or day value. Please select a valid locale.'
```

---

## SonarQube / Psalm Compliance

All changes were kept clean throughout:

| Rule | Fix applied |
|---|---|
| S1131 — trailing whitespace | Removed from docblock lines in `_form.php` |
| S1192 — string duplication (`' is-invalid'`) | Extracted to `$invalid` variable |
| S1192 — `'entry/index'` duplicated 5× in controller | Extracted to `private const ROUTE_INDEX` |
| S1192 — `'entry/index'` / `'btn btn-sm '` in view | Extracted to `$routeIndex` / `$btnSm` |
| S3358 — nested ternary | Quarter button class extracted to `$quarterClass` |
| Psalm `PossiblyInvalidMethodCall` | `getDate()` returns `DateTimeImmutable\|string\|null`; narrowed with `instanceof DateTimeImmutable` checks |
| Psalm `RiskyTruthyFalsyComparison` | `fgets() ?: ''` replaced with `!== false` guard |
| Psalm `RedundantCast` | Removed superfluous `(int)` / `(string)` casts on inferred types |
| Psalm `PossiblyInvalidArgument` (`@psalm-suppress`) | Both suppressions removed; replaced with `is_array($body)` guards |

No `@psalm-suppress` annotations remain in any PurchaseEntry file.

---

## Files Changed / Created

| File | Change |
|---|---|
| `src/Invoice/PurchaseEntry/PurchaseEntryController.php` | Added `taxYearLocales()`, `applyTaxYearLocale()`, `saveSettingValue()`; tax year settings in `index()`; `ROUTE_INDEX` constant; removed all `@psalm-suppress` |
| `src/Infrastructure/Persistence/PurchaseEntry/PurchaseEntry.php` | DDD infrastructure class (Cycle ORM, `reqId()`, `isPersisted()`) |
| `src/Invoice/PurchaseEntry/PurchaseEntryForm.php` | `show(PurchaseEntry): self` static factory; `getFormName()` returns `''` |
| `src/Invoice/PurchaseEntry/PurchaseEntryService.php` | `saveEntry()` / `deleteEntry()` |
| `resources/views/invoice/purchaseentry/index.php` | GridView + HTMX + groupBy + VAT quarter + breadcrumbs + locale link |
| `resources/views/invoice/purchaseentry/_form.php` | Add/edit form; `new Form()->open()` (PHP 8.4 style); `$invalid` constant |
| `resources/views/invoice/purchaseentry/csv_import.php` | CSRF-safe multipart form via `new Form()->csrf($csrf)->open()` |
| `resources/views/invoice/purchaseentry/tax_year_locales.php` | New — locale table with Apply buttons |
| `config/common/routes/routes.php` | Added `entry/tax-year-locales` and `entry/apply-tax-year-locale` routes |
| `resources/messages/en/app.php` | Four new `purchase.entry.*` translation keys |
