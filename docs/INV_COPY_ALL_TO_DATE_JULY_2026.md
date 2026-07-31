# "Copy All to Date" Bulk Action on inv/index — July 2026

## Background

`inv/index` already had a checkbox-driven "☑️ Copy Invoice" flow
(`multiplecopy()`/`modal-copy-inv-multiple`): tick a subset of invoices,
pick target client(s), and copy them all to a chosen date. What was missing
was a way to copy **every invoice currently in the grid** — i.e. everything
matching the active filters — to a new date, in one click, without ticking
each row and without being forced to pick a target client. Two design
questions were resolved with the user up front:

- **Scope of "all"**: every invoice currently matching the grid's active
  filters (whatever's on screen right now), not literally every invoice in
  the system regardless of filter.
- **Target client**: each invoice stays with its own original client — no
  client picker for this action.

## 1. Reused the existing copy machinery, not a new pipeline

`MultipleCopy::copyAllToDate()` (`src/Invoice/Inv/Trait/MultipleCopy.php`)
is deliberately thin: it calls `InvRepository::filterCombined($filter, $run,
$effectiveStatus)` — the exact same method the `inv/index` action itself
calls (`Index::index()`) to build the on-screen grid (so "all invoices"
always means precisely what's on screen, by construction — not a second,
potentially-drifting definition of "filtered") — then for every matching
invoice calls the same `copyInvToClient()` helper `multiplecopy()` already
uses, with the target client forced to each invoice's own `reqClientId()`
and a single `CopyInvOptions(createdDate: ...)` applied across the whole
batch. The `HomeCareRunContext` that `filterCombined()` requires is built
the same way `index()` builds it, via `Index::indexHomeCareRunContext()`.

**Correction (July 2026):** this section originally described the shared
method as `indexApplyFilters()`. That method never existed anywhere in
`InvController` or its traits — it was a copy-paste-shaped name that
silently compiled because PHP traits don't enforce a method actually
exists until it's called, and this endpoint went untested by hand. It only
surfaced when `vendor/bin/psalm --no-cache` was run project-wide and
flagged `UndefinedMethod` on the call plus a `MixedAssignment` on `$invs`
as a knock-on effect. `filterCombined()`, above, is what `index()` has
always actually called; `copyAllToDate()` now calls the same thing. A
second, unrelated Psalm finding from the same run — `CategorySecondaryRepository::optionsDataCategorySecondaries()`
only declaring `@return array` instead of `@return array<array-key,
string>` — caused a `MixedArgumentTypeCoercion` on `InvsFilterOptions`'s
constructor in `Index.php`; fixed by tightening the annotation to match
the method's actual return shape.

`indexHomeCareRunContext()` is declared `private` on the `Index` trait, but
since PHP traits are flattened into their composing class at compile time
(not scoped independently like separate classes), any other trait mixed
into the same `InvController` — `MultipleCopy` included — can call it via
`$this->` exactly as if it had been declared directly on the controller.

## 2. No selection step — the confirm dialog is the only safety gate

Every other bulk action on this grid (mark as sent, bulk quick pay, the
existing copy-multiple flow) requires an explicit checkbox selection first,
which doubles as an implicit "I chose these" confirmation. This action has
no such step by design — clicking the button after picking a date acts on
everything currently filtered. To compensate, the new modal
(`InvsToolbar::buildCopyAllToDateModal()`) carries an explicit warning
line, and the JS handler (`handleCopyAllToDate()` in
`src/typescript/invoice.ts`) requires a native `confirm()` before firing
the request — the only bulk action in this file that does.

## 3. Forwarding the grid's current filter state to a stateless endpoint

The new route (`GET|POST /invoice/inv/copyalltodate`) takes an
`InvIndexFilter` (`#[FromQuery]`) exactly like `inv/index` does. The
frontend forwards the page's current `location.search` as-is (so
`filterClient`, `filterStatus`, `filterInvNumber`, etc. all reach the
server unchanged) plus the chosen `new_date`, and separately extracts any
path-based `/status/{n}` segment via regex as a `status` fallback query
param, mirroring how `indexApplyFilters()` itself prioritises the
query-string `filterStatus` over the path-based status route argument.

## 4. New toolbar button + modal, following the existing pattern exactly

`InvsToolbar.php` already builds each modal as a self-contained static
method returning raw HTML (e.g. `buildBulkQuickPayModal()`) rather than a
separate view file — `buildCopyAllToDateModal()` mirrors that shape
precisely: a single `<input type="date">`, a warning paragraph, cancel/
confirm buttons. The "📅 Copy All to Date" button sits in the toolbar
button group right next to the existing "☑️ Copy Invoice" button.

## Verification

- Full-project `vendor/bin/psalm --no-cache` clean (see the July 2026
  correction above — this was re-verified after the `indexApplyFilters()`
  bug fix, and is accurate now).
- Full Testo suite (242 tests) still passing — no regressions from the
  `MultipleCopy.php` addition.
- `esbuild` production bundle (`invoice-typescript-iife.js`/`.min.js`)
  builds clean; both are committed alongside the source change per this
  repo's pre-commit hook.
- New route sanity-checked live (unauthenticated `GET
  /invoice/inv/copyalltodate?new_date=...` returns a `302` permission
  redirect, not a `500`), confirming the DI wiring resolves correctly.
- `npm run type-check` / `eslint` both fail on a pre-existing
  `typescript-eslint`/TypeScript 7.0 incompatibility (fails on
  `tsconfig.json` itself, before reaching any source file) — unrelated to
  this change, not introduced by it. No browser session was available in
  this environment, so an actual click-through of the modal was not
  performed.
