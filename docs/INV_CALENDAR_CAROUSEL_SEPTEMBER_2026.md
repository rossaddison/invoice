# inv/calendar — Month Carousel of Invoice "Runs" (September 2026)

## What it is

A new `inv/calendar` page: a mobile-first, swipeable `Yiisoft\Bootstrap5\Carousel`,
one slide per month, showing every day of that month as a small card. A day
that has invoices gets one badge per `(category_secondary, exact date)` "run"
present on it (e.g. "Ross Addison Secondary (1)") — clicking a badge lands on
`inv/index` pre-filtered to exactly that run, via two query params:
`filterCategorySecondaryRun` (already existed) and `filterDateCreatedExact`
(new). The page populates itself automatically from whatever `date_created`
values invoices already carry — including ones just stamped by the existing
`inv/copyalltodate` "copy to next run" feature — no separate data entry.

Entry points: a "Calendar" nav icon (📅) next to Dashboard in the invoice
layout, and a "Calendar" item in the Invoice dropdown right under "View".

The design went through two shapes before landing here, both driven by live
feedback rather than planned upfront: a static day-grid first, then a
Carousel of *weeks*, then finally — "I was hoping the Carousel Item would
hold a complete month, so viewing is quicker" — a Carousel of *months*, each
slide holding that month's whole day-grid. The final shape preloads a bounded
6-month window (5 back, 1 forward, `Trait\Calendar::MONTHS_BEFORE`/
`MONTHS_AFTER`) around whichever month is requested, in one date-range query,
so swiping through recent history feels instant without ever trying to load
the invoice table's entire history into one request.

## New/changed files

- `src/Invoice/Inv/Trait/Calendar.php` — the `calendar()` controller action,
  new trait on `InvController`. Resolves the target month from optional
  `{year}/{month}` route args, builds the preloaded month window, buckets
  invoices by day via one `InvRepository::repoDateRangeQuery()` call, builds
  each month's Monday-first week grid.
- `resources/views/invoice/inv/calendar.php` — the view. Builds each month's
  day-grid as a string via output buffering, one per `CarouselItem`.
- `config/common/routes/routes-inv.php` — `inv/calendar[/{year}/{month}]`,
  same `EDIT_INV` permission tier as `inv/index`.
- `src/Invoice/Inv/InvIndexFilter.php` / `Trait/InvCombinedFilterTrait.php` —
  new `filterDateCreatedExact` (`Y-m-d` `LIKE` match), plus a guard that
  skips the unrelated admin "current run since-date" condition when an exact
  date is already pinned (see bugs below).
- `src/Invoice/Inv/InvRepository.php` — `repoDateRangeQuery()`, a `>=`/`<`
  date-range query covering a whole multi-month window in one round trip.
- New Setting `bootstrap5_calendar_accent_color` (one of Bootstrap's variant
  names, default `primary`) — `resources/views/invoice/setting/views/
  bootstrap5/partial_calendar.php`, wired into `SettingsTabBootstrap5.php`,
  seeded in `InvoiceInstallTrait.php`. Drives the run badges, "today"'s
  border, and the carousel controls/indicators together via a
  `--calendar-accent` CSS custom property. Read directly in `Trait\
  Calendar::calendar()` (see bug below on why not the more obvious route).
- `src/typescript/calendar.ts` (+ `calendar.test.ts`) — client-side polish
  the PHP widget API genuinely can't do (see below), wired into
  `src/typescript/index.ts`.
- `src/typescript/mobile-preview-toggle.ts` (+ its own `.test.ts`) — the
  existing "📱 Mobile Preview" popup-window toggle (previously private
  inside `inv-index.ts`), extracted into its own module so this page could
  reuse it without pulling in `initInvIndex()`'s grid-specific setup
  (AmountMagnifier, group-by select, column resizer), which doesn't apply
  to a page with no invoice grid. `inv-index.ts` now imports it the same way.

## Real bugs found and fixed, in order

Every one of these was caught from a live screenshot or a live error, not
self-caught — this feature was built almost entirely through iterative
"here's what's actually on screen" feedback.

1. **Trait imported but never mixed in.** `use App\Invoice\Inv\Trait\
   {..., Calendar, ...}` (the import) was added to `InvController` without
   the actual `use Calendar;` trait-application line inside the class body —
   PHP import statements don't apply a trait. Produced
   `InvalidMiddlewareDefinitionException` ("class exists but does not
   contain method calendar()").
2. **`row-cols-md-7` is a silent no-op.** Bootstrap5's compiled CSS only
   defines `row-cols-md-1` through `-6` (`$row-cols` SCSS variable defaults
   to 6) — confirmed against the vendored `bootstrap.min.css`. The class did
   nothing, so the 7-day grid stayed single-column at every screen size.
   Fixed with a small scoped `Html::style()` block splitting
   `.calendar-week-grid > .col` into exact sevenths via `flex-basis` from
   the `md` breakpoint up.
3. **`data-bs-theme="dark"` cascades further than intended.** Tried on the
   Carousel for visible controls against a light page (Bootstrap's own
   documented "dark variant" example) — but that attribute is Bootstrap's
   *global* color-mode switch, cascading dark values for every Bootstrap CSS
   variable (`--bs-card-bg`, `--bs-body-color`, ...) to every descendant, not
   just the carousel controls. Turned the day-cards themselves solid black.
   Replaced with hand-styled controls (a solid `--calendar-accent` circle,
   not reliant on any dark-variant mechanism at all).
4. **Carousel's own click-zone swallowed badge clicks — twice.** Bootstrap's
   `.carousel-control-prev/-next` default to a hardcoded 15%-wide clickable
   zone per edge (no CSS variable to override it), landing almost exactly on
   the first/last column of a 7-up grid. First pass narrowed it to 6% plus
   matching row padding — which *reduced* the overlap but, being a fixed
   `px` padding against a percentage-of-container-width zone, never reliably
   cleared it at every viewport width. Confirmed live: badges in the
   leftmost (Monday) column rendered correctly but weren't clickable — the
   invisible zone (`position: absolute`, Bootstrap's own `z-index: 1`) was
   painting *above* the day-cards, which had no `z-index` of their own.
   Actually fixed with `.calendar-week-grid .card { position: relative;
   z-index: 2; }` — wins regardless of how much the two visually overlap,
   which padding alone never could.
5. **`border rounded` rendered as a pill/stadium shape.** A bare
   `border rounded` combo on the day-cell, at that width:height ratio,
   rendered fully rounded ends in this app's theme for reasons not fully
   root-caused (`--bs-border-radius`, `.rounded-circle`/`.rounded-pill`, and
   `.border` itself were all checked and didn't explain it). Sidestepped by
   switching to the app's own already-proven `.card`/`.card-body` component
   (same combo `client/_form.php` already uses successfully) instead of
   chasing the exact cause further.
6. **This app's compiled `.text-bg-{variant}` uses black text, not Bootstrap's
   white.** Live "black font on blue background" report traced to
   `public/assets/.../invoice/css/style.css`: `.text-bg-primary`/`success`/
   `info`/`warning`/`danger`/`light` all compute `color: #000 !important`
   (only `secondary`/`dark` get white) — confirmed against the vendored,
   *unmodified* `node_modules/bootstrap` build, which uses white for all of
   them. A real, app-wide SCSS build discrepancy, left untouched at that
   scope (flagged to the user, not fixed globally); the calendar's own
   badges get a scoped `!important` `color: #fff` override instead (had to
   be `!important` too — a plain override can never beat an existing
   `!important` rule regardless of specificity or source order).
7. **`LayoutParametersInjectionInterface` ≠ `CommonParametersInjectionInterface`.**
   The new accent-color setting was first threaded through
   `LayoutViewInjection::resolveBootstrapSettings()`, on the assumption that
   this was "the same mechanism" already making `$bootstrap5LayoutInvoiceNavbarFontSize`
   available in `invoice.php`. Wrong: that interface only reaches the
   *layout* template, not a content view rendered inside it (a *separate*
   interface, `CommonParametersInjectionInterface`, implemented by
   `CommonViewInjection`, is what makes `$s`/`$urlGenerator`/`$translator`
   available everywhere). Produced a real `ErrorException: Undefined
   variable $bootstrap5CalendarAccentColor`. Fixed by reading the setting
   directly in `Trait\Calendar::calendar()`'s own view-parameters array —
   the correct pattern for a value only one content view needs — and the
   now-dead entry was removed from `LayoutViewInjection.php` again.
8. **Carousel indicators pinned to the bottom of a variable-height container.**
   Bootstrap's default `.carousel-indicators` (`position: absolute; bottom:
   0`) pins them to the bottom of the *carousel container*, which — having
   no fixed height — sizes itself to the active slide's full multi-week
   grid. Instead of sitting under everything, they visually overlapped
   whichever row happened to land at that computed bottom edge. Fixed with
   `position: static` — Bootstrap's own `renderItems()` already outputs the
   indicators `<div>` before `.carousel-inner` in the DOM (confirmed in the
   widget source), so static flow puts them at the actual top of the
   carousel with no extra markup needed. Per further live feedback ("swap
   their positions"), the month-navigation Prev/Today/Next buttons moved
   from above the grid to below it, and the always-stale static page-title
   heading (never updated while swiping) was dropped entirely once nothing
   sat directly above the now-relocated dots to justify keeping it.
9. **`CarouselItem::attributes()` exists but is never applied.** Confirmed by
   reading `Carousel::renderItem()`'s source: it reads `getContent()` and
   `getCaption()` from a `CarouselItem` but never `getAttributes()` — so
   passing `attributes: [...]` to `CarouselItem::to()` silently does
   nothing to the rendered `.carousel-item` div. Needed for two features
   (per-slide indicator tooltips naming the month, and ringing the
   indicator for the real-world current month): worked around by reading
   each slide's own `<h5>` month heading (and a `data-current-month`
   attribute on it) from `calendar.ts` instead, since that's markup this
   view's own PHP fully controls, unlike the widget's outer wrapper.

## Beautification (explicit request: "some typescript")

- Bootstrap's stock control icon is a CSS background-image mask with no
  widget API to swap it — `calendar.ts` replaces it with a real
  `bi-chevron-left/-right` glyph (this app's existing icon font) inside a
  solid `--calendar-accent` circle.
- Left/Right arrow-key paging while the carousel has focus
  (`bootstrap.Carousel.getOrCreateInstance(el).prev()/.next()`).
- A hover/focus magnifier on each run badge, the same idea as
  `AmountMagnifier` (`list-utils.ts`) already applies to inv/index's amount
  pills — not reused directly since that class only matches badges whose
  text looks like a bare formatted number; click still navigates normally
  (no click-to-toggle, unlike `AmountMagnifier`'s non-link badges).
- A tooltip naming the month on each indicator dot, forced to open
  `bottom` rather than Bootstrap's default `top` (the dots sit at the very
  top of the page content, so a top-opening tooltip had page chrome, not
  space, to open into).
- A ring on whichever indicator dot is the real-world current month
  (`--bs-warning`), independent of Bootstrap's own `.active` (whichever
  slide is currently *viewed* — not necessarily the same one).
- The "📱 Mobile Preview" popup-window toggle, same as inv/index — explicit
  request, "importantly view it on the mobile" — extracted from
  `inv-index.ts` into its own module (see above) so this page could reuse it
  without a grid to attach `AmountMagnifier` etc. to.
- Each day box's weekday abbreviation moved onto its own small, uppercase,
  muted line above a larger bold date number (was inline "Mon 31"); every
  in-month day box gets a slight `bg-light` tint instead of blending flat
  into the page background.

## Verification

- `php -l` clean on every touched/new PHP file throughout.
- `vendor/bin/psalm --no-cache` clean at every stage (`InvController.php`
  full-class analysis, `LayoutViewInjection.php`, `SettingsTabBootstrap5.php`,
  `SettingController.php`, `InvoiceInstallTrait.php`).
- `npm run type-check` (tsc --noEmit) clean throughout.
- Full `npm run test` (vitest): 220/220 passing across 17 files by the end
  (14 in `calendar.test.ts` alone, plus a new `mobile-preview-toggle.test.ts`
  and the pre-existing `inv-index.test.ts` suite unaffected by the
  extraction).
- `npm run build:typescript:prod` rebuilt after every TypeScript change —
  the committed `src/Invoice/Asset/rebuild/js/invoice-typescript-iife*.js`
  bundles are what the live app actually serves.
- `php yii router/list` confirmed `inv/calendar` registered after every
  controller/route change.
- Live-confirmed by the user across many rounds: the route resolving, the
  7-column desktop grid actually rendering, contrast fixes, the click-zone
  fix, the month-carousel redesign, and the final dots/buttons layout swap.

(September 2026)
