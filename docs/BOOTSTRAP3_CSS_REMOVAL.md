# Bootstrap 3 CSS Removal (May 2026)

Incremental removal of InvoicePlane's legacy Bootstrap 3 styles from
`src/Invoice/Asset/invoice/css/style.css` and replacement of Bootstrap 3
class names with their Bootstrap 5 equivalents across all view files.

## Background

`style.css` is a compiled bundle containing Bootstrap 5.3.8, Select2,
Datepicker, and a custom InvoicePlane section (originally lines 13500–14466).
The custom section carried styles written for Bootstrap 3 that either duplicate
Bootstrap 5 behaviour, reference classes that no longer exist, or were simply
dead code with zero references in the view layer.

SonarCloud was also reporting hundreds of "Duplicate selector" warnings caused
by Bootstrap 5 and Select2's own internal repetitions. Those are third-party
files that should not be analysed.

## Changes

### `sonar-project.properties`

Extended the exclusion path from `src/Invoice/Asset/rebuild/**` to
`src/Invoice/Asset/**` so SonarCloud no longer analyses the compiled CSS/JS
bundle (Bootstrap 5.3.8 duplication warnings eliminated).

### `style.css` — custom section reduced by 32 % (966 → 653 lines)

#### Dead Bootstrap 3 classes removed (no view references)

| Removed rule | BS3 class |
|---|---|
| `.navbar .navbar-toggle` / `:hover` | `.navbar-toggle` |
| `.navbar .navbar-form` | `.navbar-form` |
| `.navbar .navbar-collapse` | `.navbar-collapse` |
| `.navbar .navbar-nav .fa` | selector no longer matches |
| `.navbar-inner` | `.navbar-inner` |
| `.navbar-search` | `.navbar-search` |
| `.table .input-group-addon` | `.input-group-addon` |
| `.discount-field .input-group-addon` | `.input-group-addon` |
| `.discount-field .input-sm` | `.input-sm` |
| `#login .form-horizontal` | `.form-horizontal` |
| `#login .form-group` | `.form-group` |
| `.tabbable .table` | `.tabbable` |
| `#submenu .nav-pills > li > a` | `#submenu` uses `nav-tabs`, not `nav-pills` |
| `.dropdown-button` (full block) | replaced by BS5 `.dropdown-item` |
| `.navbar-nav .visible-lg` (media query) | `.visible-lg` |
| `.text-left-xs` (media query) | `.text-left-xs` |

#### Bootstrap 5 already provides these — removed as redundant

| Removed rule | BS5 equivalent |
|---|---|
| `textarea { resize: vertical }` | BS5 Reboot sets this |
| `img { vertical-align: middle }` | BS5 Reboot sets this |
| `.navbar-nav .d-inline-block { display: inline-block !important }` | BS5 utility already sets `!important` |
| `#submenu .submenu-row` (duplicate) | merged into single rule |
| `#content .row { margin-left/right: -10px }` (767 px) | BS5 grid manages own gutters |
| `.navbar-nav { margin: 7px 0 }` (767 px duplicate) | already covered by 991 px rule |

#### Dead custom rules removed (zero view references)

`.fa-credit-invoice:before/:after`, `#email_template_pdf_template > option`,
`#ipnews-results`, `.model-pager .btn .fa`, `.login-logo`,
`.passwordmeter-input`, `.alert.alert-default`, `.personal_logo`,
`.no-border-radius`, `.padded`, `#settings-tabs` (both rules), `body.error`
(entire block — `#ip-logo`, `div.error-container`, headings),
`#panel-overdue-invoices` selectors, `#panel-recent-invoices` selectors
(IDs in HTML now carry `-1`/`-2` suffixes).

#### Selector simplifications

- `legend, .install-step h2, .install-step .h2 { … }` → `legend { … }`
  (`.install-step` has zero view references)
- `#panel-overdue-invoices .card-body, #panel-recent-quotes .card-body,
  #panel-recent-invoices .card-body` → `#panel-recent-quotes .card-body`
  (only that ID exists in the dashboard HTML)
- `@media (max-width: 1199px) { #submenu .submenu-row { … } }` removed —
  rule promoted to unconditional after finding identical declaration in
  `@media (min-width: 1200px)`; together they covered 100 % of viewports

#### Bug fix

`.table { font-size: 0.25rem }` — 4 px is unreadable; this line was a
compilation artefact. Removed so tables inherit the body font-size (1 rem).

#### Accessibility improvement

`body *:focus { outline: none !important; box-shadow: none !important }` —
blanket suppression of all focus indicators (WCAG 2.1 violation). Removed;
Bootstrap 5's built-in focus rings are now active across the application.

#### Structural fixes

`@media (max-width: 767px)` block had `.headerbar { padding: 8px !important }`
and `.float-end { margin-left: 5px !important }` preserved (genuinely needed),
while the redundant `.navbar-nav { margin: 7px 0 }` and the BS3-era
`#content .row` negative-margin hack were removed from that block.

### View files — Bootstrap 3 class replacements

| Old class | New class | Files | Occurrences |
|---|---|---|---|
| `dropdown-button` | `dropdown-item` | 15 | 23 |
| `input-sm form-control` | `form-control form-control-sm` | 4 | 12 |
| `no-padding` | `p-0` | 1 | 2 |
| `form-group` | `mb-3` | 104 | 484 |

### `resources/views/invoice/setting/tab_index.php`

`ul.nav.nav-tabs` given `justify-content-center` (BS5 flexbox approach) to
replace the removed `#settings-tabs { text-align: center }` /
`#settings-tabs > li { float: none; display: inline-block }` rules, which used
the Bootstrap 3 inline-block centering technique and conflicted with BS5 flex.

## What Was Kept

Rules retained because they are genuinely custom, still referenced in views, or
required for PDF template rendering (which cannot use Bootstrap utility classes):

- `html`, `body` min-height / overflow
- `img { max-width: 100% }` — not set globally by BS5
- `h1–h6 { margin: 0 }` — deliberate heading margin override
- `fieldset`, `fieldset legend`, `label`, `form` resets
- `.discount-field` — invoice item table (views still use it)
- `.table.items td.*`, `#item_table` — invoice item table layout
- `.no-margin`, `.amount` — used in PDF templates (cannot substitute `m-0`/`text-end`)
- `.draft`, `.sent`, `.viewed`, `.paid`, `.label.*` — invoice status colours
- `.invoice.*`, `.quote.*`, `.overview` — invoice/quote print layout
- `#actions` — Dropzone file upload widget
- `#fullpage-loader`, `#headerbar`, `#main-area`, `#submenu`, `.sidebar`
- `.cursor-pointer`, `.cursor-move`, `.fa`, `.fa.fa-margin`
- `.alert { margin-left/right: 15px }` — layout margin override
- `#login`, responsive media query blocks
