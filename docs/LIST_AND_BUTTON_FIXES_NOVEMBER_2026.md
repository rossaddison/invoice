# Smaller fixes: invoice toolbar buttons, a sales-order badge, the settings debug page, the invoice list — November 2026

## In one paragraph

Four small faults found while using the site on 22 to 24 November, each with a test. They share two causes: the
**Content-Security-Policy refuses inline JavaScript**, and **HTML that was already built got escaped a second time**.

## The Content-Security-Policy and inline handlers

`script-src` has no `'unsafe-inline'` (it is set in both `public/.htaccess` and `ContentSecurityPolicyMiddleware`), so a
button with `onclick="..."` or a drop-down with `onChange="..."` does nothing: the browser silently refuses the handler.
The app's CSP-safe patterns are the `native-reset` class (a delegated listener in `src/typescript/data-actions.ts`), the
`data-confirm` attribute, and the vendor `YiiDataViewNoInlineJsAsset`.

| Where | Fault | Now |
|---|---|---|
| **Invoice toolbar** (`ButtonsToolbarFull`) | The Peppol, Storecove JSON, Ecosio validator and Chromium PDF buttons were `href="#"` with an inline `window.open(...)`, so they only jumped to the top of the page | Real links with `target="_blank"` and `rel="noopener noreferrer"` |
| **`setting/debug_index`** | The value filter drop-down and the delete button used inline handlers; wide values pushed the row buttons off screen (`html { overflow-x: hidden }` clips them) | `native-reset` on the drop-down, `data-confirm` on delete, `table-responsive`, wrapping text and top-aligned rows; filter labels are capped at 40 characters and de-duplicated; a stale Psalm suppression was removed |

**Still open:** about sixty other view and widget files still use inline handlers (for example `QuoteToolbar.php`). That
is a count from a search, not a list of known-broken buttons, and none of them has been changed.

## Escaped HTML

The **Sales order list** showed the status for an order that is ready to become an invoice (status 7, "Invoice Generate")
as the literal text `<span class="badge …">`. The badge was rendered first and then passed as the content of a link;
`Html::tag()` escapes its content unless told not to. The link now uses `->encode(false)` and the cell shows the badge.

## The invoice list in window-cleaning mode

A window-cleaning install (**Business type**, see [Home-services mode is reversible](WINDOW_CLEANING_MODE_REVERSIBLE_NOVEMBER_2026.md))
has no quotes or sales orders and its menus already leave them out, but the staff **invoice list** still showed the
**quote link**, **sales-order link** and 🔀 **workflow** columns, which could only ever be empty. `InvsColumnBuilder` now
leaves them out in that mode. Every other install, including one with no business type saved, keeps them. The delivery
location column and the rest of the grid are unchanged.

## Tests

`ButtonsToolbarFullWindowButtonsTest`, `DebugIndexCspTest`, `DebugIndexLayoutTest`, `SettingValueFilterOptionsTest`,
`SalesOrdersStatusColumnTest`, and `InvsColumnWindowCleaningTest` (builds the real column list for standard, unsaved and
window-cleaning installs). They build and render the real pieces; none of them opens a browser.
