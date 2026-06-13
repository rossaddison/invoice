# Invoice Index — Workflow Type Badges

**Branch:** `sonarqube-parameter-reduction`  
**Date:** June 2026

## Problem

`inv/index` showed every invoice in a uniform grid with no visual indication of
how it was created.  A standard invoice created directly ("new invoice") looked
identical to one that followed the full Peppol observer workflow:
**Quote → Sales Order → Invoice** (where an external guest/observer submitted
purchase-order details via the Sales Order step before the invoice was raised).

## Solution

A new always-visible column was added to `InvsListWidget` at position 2 (immediately
after the checkbox column, before the edit button).  It renders a compact
colour-coded badge based on the invoice's `quote_id` and `so_id` fields:

| Badge | Colour | Meaning | Tooltip |
|-------|--------|---------|---------|
| 🔀 | Blue (`bg-primary`) | Full Peppol workflow — `so_id` is set | "Quote → Sales Order → Invoice (Peppol Universal Business Language)" |
| 💬→📄 | Teal (`bg-info`) | Quote-derived — `quote_id` set, no SO step | "Quote → Invoice" |
| 📄 | Grey (`bg-secondary`) | Standalone invoice — no quote, no SO | "Invoice" |

Badge labels are emoji-only so the column stays narrow.  The full descriptive
text appears as a Bootstrap tooltip on hover.  All label strings are fed through
the translator so they follow the active UI language.

## Group-by support

`peppol_workflow` was added to the **Group By** dropdown in the toolbar.
Selecting it collapses the list into three named groups:

- `Peppol (Quote → SO → Invoice)`
- `Quote → Invoice`
- `Standard Invoice`

Each group header shows the invoice count and running totals (total / paid /
balance) in the same style as all other group-by modes.

## Files changed

| File | Change |
|------|--------|
| `src/Invoice/Inv/Widget/InvsListWidget.php` | Added `buildWorkflowTypeColumn()`; inserted it at column index 1 in `buildColumns()`; added `peppol_workflow` option to groupBy dropdown and resolver |

## Implementation detail

```php
private function buildWorkflowTypeColumn(): DataColumn
{
    $t = $this->translator;
    return new DataColumn(
        header: ...,           // 🔀 with tooltip listing all three modes
        content: static function (Inv $model) use ($t): string {
            if ($model->getSoId() !== null) {
                return '<span class="badge bg-primary" ...>🔀</span>';
            }
            if ($model->getQuoteId() !== null) {
                return '<span class="badge bg-info text-dark" ...>💬→📄</span>';
            }
            return '<span class="badge bg-secondary" ...>📄</span>';
        },
        encodeContent: false,
        withSorting: false,
    );
}
```

`getSoId()` takes precedence: an invoice that went through the full
Quote → SO → Invoice chain has both `quote_id` and `so_id` set; the blue 🔀
badge fires first so it always represents the most complete workflow.

Psalm errorLevel 1 — no issues.
