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

## Extended to the guest-facing layout — per-observer, not admin-shared

`resources/views/layout/guest.php`'s own `NavBar::widget()` had a
*hardcoded* `->placement(NavBarPlacement::STICKY_TOP)` — always sticky,
regardless of anyone's preference. The first attempt at fixing this
reused the same admin-controlled shared settings the four staff grids
use (`bootstrap5_layout_invoice_navbar_sticky`/`grid_sticky_header`),
reasoning "one setting, not four" the same way `grid_sticky_header`
itself was justified. That reasoning doesn't hold on the guest side: an
admin and an anonymous-to-them customer/observer are different people
making a personal display choice, not one admin deciding a house style
for every staff grid — corrected before merge to a genuine **per-observer
self-service preference** instead, mirroring the exact pattern this app
already uses for the observer's own list-size choice
(`UserInv.listLimit` + `UserInvController::guestlimit()` +
`Widget\PageSizeLimiter::buttonsGuest()`).

- **Two new `UserInv` boolean columns**, `sticky_navbar`/
  `sticky_grid_header` (`UserInv.php`/`Trait\UserInvTrait4.php`) — same
  `#[Column(type: 'bool', typecast: 'bool', default: false)]` shape as
  every other observer-facing boolean already on this entity
  (`consent_periodic_invoice`, `active`, ...). Default `false`, matching
  both this entity's own convention and the admin-side settings' own
  installer default (`InvoiceInstallTrait.php`: both `0`) — no visual
  change for an existing install until an observer opts in themselves.
- **Two new toggle actions**, `UserInvController::guestStickyNavbar()`/
  `guestStickyGridHeader()`, an exact mirror of `guestlimit()`'s own
  shape (load the observer's own `UserInv` by id, flip the field, save,
  redirect back to `$origin . '/guest'`) — sharing one private
  `toggleGuestBooleanPreference(int, string, uiR, \Closure)` helper
  between the two rather than duplicating the load/save/redirect
  boilerplate twice, the same duplication concern that already drove
  `SettingToggleController::toggleBooleanSettingCreatingAtOne()`'s own
  extraction on the admin-setting side of this feature. Two new routes,
  `userinv/guestStickyNavbar` / `userinv/guestStickyGridHeader`
  (`routes-user-inv.php`), same `RoutePermission::check(Permissions::
  EDIT_USER_INV)` gate as `userinv/guestlimit`.
- **Resolved in `LayoutViewInjection::resolveUserState()`**, not
  `GuestLayoutViewParameters` — that class has no `UrlGenerator`/
  `CurrentRoute` to build a toggle link from, and `resolveUserState()`
  already resolves this exact observer's own `UserInv` row for the
  identical page-size-preference purpose immediately above it, so the
  two new values are computed alongside it in the same guarded block
  (`!$isGuest && $user !== null`) rather than a second lookup.
  `guestStickyNavbar`/`guestStickyGridHeader` (current on/off state) and
  `guestStickyNavbarToggleUrl`/`guestStickyGridHeaderToggleUrl` (the
  link to flip it) are exposed to every layout, `guest.php` included,
  the same way `guestPageSizeUrlTemplate` already is.
- **`guest.php`** now reads `$guestStickyNavbar` (not the old shared
  setting) for both the `.sticky-top` class and the `--sticky-content-top`
  CSS variable, and gained two ON/OFF link-button pairs in the existing
  Settings gear dropdown, right below the list-size buttons — plain
  `<a href>` links (not `hx-get`, unlike the list-size buttons: both
  preferences are read while this layout builds its own `<head>`/navbar
  markup server-side, so a full page reload is genuinely needed to see
  the new state, not just a background save). Empty toggle URLs
  (anonymous visitor, no `UserInv` row) simply render inert `href=""`
  buttons rather than being hidden outright — harmless, matches how the
  Settings dropdown itself is only shown to a logged-in observer anyway
  (`if (... && !$isGuest)`).
- **`inv/guest.php`** now reads `$userInv->getStickyGridHeader()` instead
  of `$s->getSetting('grid_sticky_header')` — `$userInv` was already an
  existing view var here (`Guest.php`'s own `renderGuestView()` already
  passes the observer's own `UserInv` for the list-size preference), so
  no new plumbing was needed to reach the content view, unlike the
  layout-level navbar preference above.
- New translation keys: `on`/`off` (reused by both button pairs),
  `sticky.navbar`/`sticky.navbar.hint`, `sticky.grid.header`/
  `sticky.grid.header.hint`.
- The admin-controlled `bootstrap5_layout_invoice_navbar_sticky`/
  `grid_sticky_header` settings and their gear-dropdown toggles on
  `invoice.php` are untouched — staff keep their own shared setting for
  the four staff grids and `invoice.php`'s own navbar; only the
  guest-facing layout and `inv/guest.php`'s grid moved to a per-observer
  model.

A real SonarCloud `new_duplicated_lines_density` gate failure (8.75%,
threshold 3%) surfaced on the PR for this redesign — not the already-known
`new_coverage` gap, a genuine finding: the two new route definitions
repeated `routes-user-inv.php`'s existing `Route::methods(...)->name(...)
->middleware(...)->action(...)` shape once too often, and that same shape
already ran nine times in a row for the admin `userinv/*` CRUD/role
routes. Fixed by extracting two small closures at the top of the file,
`$adminUserInvRoute`/`$guestUserInvRoute` (one per `RoutePermission`
gate), and reducing all thirteen route definitions in the file to one
call each — same paths, names, and actions, byte-for-byte, confirmed via
`php yii router/list`.

A real live crash surfaced right after this shipped:
`Yiisoft\Router\RouteNotFoundException: Cannot generate URI for route
"invoice/guest"` on clicking the sticky-navbar toggle from
`invoice/index` (the staff/observer dashboard, unrelated to `inv/*` the
Invoice document list). Root cause, shared with the pre-existing
`guestlimit()`/`PageSizeLimiter` page-size buttons this feature's own
buttons were modeled on: the redirect target was guessed from the
*current* route name's first `/`-segment (`$guestOrigin`) on the
assumption the observer is always on one of the "X/guest" content pages
(`inv/guest`, `client/guest`, ...) when clicking — true for a button
embedded in one of *those* pages, false for one embedded in the shared
guest layout's own menu, which renders on every guest page, `invoice/*`
dashboard pages (a real, distinct route-name namespace, unrelated to
`inv/*`) and `auth/change` included. `LayoutViewInjection::
resolveUserState()` now also computes the observer's actual current path
(`CurrentRoute::getUri()?->getPath()`) and passes it as a `redirect`
query parameter alongside the existing `origin`-based route arguments on
all three toggle URLs (page-size included, fixed at the same time since
it's the identical defect in code already being touched); `guestlimit()`/
`toggleGuestBooleanPreference()` now prefer
`WebControllerService::getRedirectToSameOriginPathResponse()` (the same
open-redirect-safe helper `Webshop\Delivery\DeliveryController` already
uses for "redirect back to wherever the visitor actually was") over the
`$origin`-derived guess when a `redirect` value is present, keeping the
old behaviour only as a fallback for a stale/bookmarked toggle URL
predating this fix.

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

The per-observer redesign above: `php -l` clean on every touched file;
full-project `vendor/bin/psalm --no-cache --no-progress` — "No errors
found!" (exit 0, same 201 pre-existing informational issues, unchanged).
Full Testo Unit suite 1256/1256, count unchanged — the two new controller
actions weren't given dedicated Testo coverage, following the exact same
pre-existing-gap reasoning already documented above for
`gridStickyHeader()`/`navbarSticky()` (`guestlimit()` itself, the pattern
these two mirror, has never had test coverage either). New
`Tests/Unit/Invoice/Entity/UserInvEntityTest.php` coverage for the two new
fields (`testStickyNavbarDefaultsFalse`/`testSetAndGetStickyNavbar`/
`testStickyGridHeaderDefaultsFalse`/`testSetAndGetStickyGridHeader`) — full
file 18/18. No schema migration run against a live database — this
checkout has no DB configured (same limitation already noted above), so
the new `sticky_navbar`/`sticky_grid_header` columns need a
`BUILD_DATABASE`-driven Cycle ORM schema sync on first deploy, same as
any other new entity column. Not yet independently live-browser-confirmed.

Two real production crashes surfaced live once deployed, both fixed
same-day: (1) the schema-sync gap flagged above actually hit — a real
`Typed property ...UserInv::$sticky_navbar must not be accessed before
initialization` on yii3i.online, confirming the columns hadn't been
synced yet; (2) the `RouteNotFoundException` described above, found by
actually clicking the new sticky-navbar button live from the
`invoice/index` dashboard. The redirect-target fix: `php -l` clean,
full-project Psalm clean (exit 0, 201 pre-existing informational issues
unchanged), full Testo Unit suite 1256/1256 unaffected, `php yii
router/list` confirms all three guest routes still register correctly.
