# Sticky Navbar + Sticky Grid Header (September 2026)

Two related opt-in layout toggles, built in the same session and ending
up in the same place in the UI: the top navbar and every grid's column
headers can now stay pinned in place while the page scrolls underneath
them. Both started life differently — one setting per feature, buried in
Settings tabs — and were deliberately consolidated once the second one
made the pattern obvious.

## Sticky navbar

`resources/views/layout/invoice.php`'s `NavBar::widget()` gets Bootstrap's
own `.sticky-top` utility class (`position: sticky; top: 0; z-index: 1020`)
plus `bg-body-tertiary` (the navbar has no background class at all
otherwise, so without it page content visibly showed through a
transparent sticky bar) — both only when the setting is on, via a small
conditional after the widget is built rather than inside the fluent
chain.

## Three real CSS bugs found tracing why it didn't work

Turning the setting on didn't actually stick the navbar, three times in a
row, each fix revealing the next layer:

1. **`body` had `overflow-x: hidden; overflow-y: scroll;`**
   (`src/Invoice/Asset/core/scss/_core.scss`). `position: sticky` sticks
   relative to the nearest *scrolling* ancestor — setting `overflow` to
   anything but `visible` on `body` makes `body` itself that ancestor
   instead of the viewport, silently breaking sticky positioning on all
   of `body`'s children in every major browser. Fixed by moving the
   overflow declaration up to `html` instead — identical visual result
   (no horizontal scroll, a permanent vertical scrollbar so page-height
   changes don't shift layout), but `html` stays the real scrolling
   element.
2. **The identical bug, duplicated in a second, un-synced file.**
   `src/Invoice/Asset/invoice/css/base.css` — part of a partially
   migrated split of `style.css` into `variables.css`/`base.css`/
   `layout.css`/`components.css`/`utilities.css`/`overrides.css` (each
   file's own header says `TODO: Complete migration ... from style.css`)
   — carried its own separate copy of the same `body { overflow-x:
   hidden; overflow-y: scroll; }` rule. Fixing `_core.scss` alone wasn't
   enough; `base.css` kept the bug alive on its own. First attempt at
   this fix actually missed the second copy entirely — added the `html`
   version but forgot to delete the original `body` one from the same
   file, confirmed wrong by reading back the actual *published*
   `public/assets/<hash>/.../base.css`, not just the source file.
3. **Cascade-order tie.** `components.css` (loaded after `style.css`,
   also labelled "From style.css") independently declares `.navbar {
   position: relative; ... }`. Equal specificity to `.sticky-top`, later
   in load order — it silently won and cancelled `position: sticky`
   outright, no console error, no visual hint why. Fixed with a
   `.navbar.sticky-top { position: sticky; ... }` rule in
   `overrides.css` (two classes beats one, and it's the last stylesheet
   loaded either way) rather than trying to reconcile the duplicate
   split-CSS system.

All three were found by pasting the actual served HTML/CSS back for
inspection at each "still not working" report, not by guessing — the
real `<head>` order and the real published asset files, each round.

## Offset for a navbar that wraps

A static `top: 50px` guess (`--navbar-height`, already an existing CSS
variable in `variables.css`) undershoots once the navbar wraps onto a
second line — confirmed from a real screenshot at an ordinary viewport
width where the FAQ's/Generator/Performance row filled first and
Platform/emoji dropdowns wrapped to a second, leaving anything sticking
to the static value tucked partly under that second row. Replaced with
a live measurement: new `src/typescript/sticky-navbar-offset.ts`
(`initStickyNavbarOffset()`, wired into `index.ts`) finds `nav.navbar.
sticky-top`, reads its real `offsetHeight`, and writes it into a
`--sticky-content-top` CSS variable via a `ResizeObserver` — not just a
one-time load measurement, since the navbar's height can change from a
locale switch, font-size zoom, or content reflow, not only a viewport
resize. No-ops entirely when the navbar isn't sticky.

## Sticky grid header, generalized across all four grids

The same idea for `inv/index`'s table header, then explicitly asked to
extend to the other three list grids (Quote/SalesOrder/Product) "using
the DRY principle":

- **One CSS rule**, not four: `.sticky-grid-header thead th` in
  `overrides.css`, deliberately not scoped to any one `#table-id` —
  every grid already renders the same `bg-info` header row via
  `headerRowAttributes`, so one rule (with the same cascade-order
  reasoning as the navbar fix above) covers Invoice/Quote/SalesOrder/
  Product alike, present and future. `top: var(--sticky-content-top)`
  reuses the exact same navbar-offset variable, so a sticky grid header
  sits flush below a sticky navbar when both are on instead of
  underneath it. `z-index: 2`, below `.col-resize-handle`'s existing
  `z-index: 3` so column resizing still works on a stuck header.
- **One shared setting**, not four: `grid_sticky_header`. There's no
  real reason someone wants sticky headers on invoices but not quotes.
  Each `*ListWidget` class (`InvsListWidget`, `QuotesListWidget`,
  `SalesOrdersListWidget`, `ProductsListWidget` — no shared base class
  between them, confirmed by reading all four constructors) gets the
  same three-line addition: a `bool $stickyHeader` property, a setter
  (folded into `InvsListWidget::withGridDisplayOptions()`'s existing
  bundler to stay under that class's own 20-method S1448 ceiling;
  standalone `withStickyHeader()` on the other three, which had
  headroom), and `' sticky-grid-header'` appended to the table's class
  string when it's on.
- **Two call sites per grid**, not one — caught before shipping, not
  after: each grid has a normal page-render path *and* a separate
  HTMX-partial-refresh path (`if ($request->hasHeader('Hx-Request'))`,
  used for in-place pagination/sorting). Missing the second one would
  have silently dropped the sticky class the moment a user sorted or
  paged the grid — checked explicitly for all four grids
  (`grep -rl "hasHeader('Hx-Request')"` across each domain) rather than
  assuming only Invoice needed it, since that's exactly the shape of bug
  already found once.

## Where the toggles ended up

Not a Settings-tab form field for either one, in the end. Both moved to
the navbar's own gear-icon dropdown, directly below "Number of Items in
Lists" — the same immediate-save, no-page-reload UX (`hx-get` +
`hx-swap="none"`, the checkbox's own native click already shows the new
state; the request just persists it in the background). New
`SettingToggleController::gridStickyHeader()` and `::navbarSticky()`,
each an exact mirror of the controller's existing `visible()` toggle
action — same shape, same `Setting`-row save, same origin-based redirect
for a no-JS fallback. `bootstrap5_layout_invoice_navbar_sticky`'s
Settings → Bootstrap5 checkbox was removed outright once the gear-dropdown
toggle replaced it (`partial_navbar_invoice.php`,
`SettingsTabBootstrap5::buildBootstrap5Body()`); `grid_sticky_header`
started life as `inv_grid_sticky_header` on a Settings → Invoices field
and was renamed before ever shipping once the "one setting, not four"
design was settled.

## Also fixed along the way

- A SonarQube `php:S1192` finding (`Define a constant instead of
  duplicating this literal "/index" 5 times`) that the new
  `gridStickyHeader()`/`navbarSticky()` methods pushed over the
  threshold in `SettingToggleController` — extracted `INDEX_SUFFIX`
  rather than leaving it, per this repo's own stated priority on
  reducing string duplication.
- A bad `sed` substitution mid-refactor that produced `$origin$origin .
  self::INDEX_SUFFIX` — caught by re-reading the file immediately after
  running it, fixed before it was ever committed.

## Extended to the guest-facing layout

`resources/views/layout/guest.php`'s own `NavBar::widget()` had a
*hardcoded* `->placement(NavBarPlacement::STICKY_TOP)` — always sticky,
regardless of the shared `bootstrap5_layout_invoice_navbar_sticky` setting
staff could toggle for `invoice.php`. There's no real reason a guest
customer's navbar should behave differently from staff's own — same
"one setting, not four" reasoning `grid_sticky_header` already
established — so it now reads the exact same shared setting
(`$bootstrap5LayoutInvoiceNavbarSticky`, already globally injected via
`LayoutViewInjection` regardless of layout) and applies `.sticky-top`
conditionally, mirroring `invoice.php`'s own pattern exactly (`placement()`
is a thin wrapper over `addClass()`, confirmed from the widget's own
source, so the two are truly equivalent). `guest.php` gained the same
`--sticky-content-top` custom property `invoice.php` already declares — no
new CSS was needed at all: `.navbar.sticky-top`/`.sticky-grid-header thead
th` in `overrides.css`, and the `html`-not-`body` overflow fix, are all
global rules `guest.php` already inherits via the same `InvoiceCdnAsset`/
`InvoiceNodeModulesAsset` bundles `invoice.php` uses, and
`initStickyNavbarOffset()` was already active on guest pages (`index.ts`
calls it unconditionally, and `inv/guest.php` already invokes
`InvoiceApp.initInvIndex('table-invoice-guest', ...)` from the same
bundle).

The guest-facing invoice list itself (`resources/views/invoice/inv/
guest.php` — HomeCare workers and ordinary client observers both land
here, reached via `Guest.php`'s own `guest()` action) builds its
`GridView` directly rather than through one of the four `*ListWidget`
classes the staff grids use, so the `sticky-grid-header` class is appended
to its `tableAttributes` inline instead of via a `withStickyHeader()`-
style setter — same shared `grid_sticky_header` setting, same
already-`bg-info` header row the shared CSS rule matches, no per-view CSS
needed. Unlike the four staff grids, this view has no separate
HTMX-partial-refresh path to also update (`guest()` always does a full
page render), so there was only the one call site.

## Verified

`php -l` clean on every touched file. Targeted `vendor/bin/psalm
--no-cache` clean on every touched file (full-project run not repeated
mid-session given the volume of small, individually-verified changes).
Full PHPUnit suite: 3954/3954 (up from 3951 — two new
`testWithStickyHeaderReturnsNewInstance`-style tests per sibling widget:
`InvsListWidgetTest`, `QuotesListWidgetTest`, `SalesOrdersListWidgetTest`,
`ProductsListWidgetTest`). Full Testo Unit suite: 1226/1226, unchanged
count (this feature's controller/view wiring isn't covered by the Testo
suite — `SettingToggleController`'s existing sibling toggle actions
were already untested before this, and `gridStickyHeader()`/
`navbarSticky()` were added following that existing, pre-established
pattern rather than introducing new test infrastructure disproportionate
to it). `tsc --noEmit` clean; `npm run build:typescript:prod` confirmed
the new module lands in the compiled bundle. Not independently
live-browser-tested by Claude in this session (no DB configured in this
checkout) — every fix in the three-bug chain above was instead verified
by reading back the user's own pasted HTML and this repo's actual
published `public/assets/<hash>/...` files after each round, and the
final result was confirmed working live by the user directly.

The guest-layout extension above: `php -l` clean on both touched files,
`vendor/bin/psalm --no-cache` clean both in isolation and on a full-project
re-run (201 pre-existing informational issues unchanged, 0 errors), full
Testo Unit suite 1256/1256 unaffected. No CSS/JS changes needed at all —
purely reusing the existing shared settings, rules, and TypeScript module.
Not yet independently live-browser-confirmed for the guest layout
specifically.
