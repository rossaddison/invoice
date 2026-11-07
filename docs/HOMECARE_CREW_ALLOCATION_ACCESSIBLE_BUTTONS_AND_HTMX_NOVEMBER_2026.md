# HomeCare Crew Allocation: Accessible Buttons and No-Reload Taps (November 2026)

On `inv/guest` the HomeCare **lead** allocates each house's Front and Back
(or the whole house) to today's crew with one tap per worker, and the
allocated worker, or the lead, settles each side as done, postponed or
cancelled. The lead does this in split seconds across a whole street, and
at least one worker is colour-blind and dyslexic, so this round made the
controls readable without colour or words, finger-sized, and instant.

## One widget for every button

`src/Invoice/Inv/Widget/AllocationButtons.php` owns every allocation
control: the crew chips, the done / postpone / cancel buttons, the status
pill and the fallback side+worker form. `InvAllocationCell` keeps only the
layout and who-may-see-what. The same widget is what the admin `inv/index`
grid can reuse later. Repeated literals are class constants (SonarCloud
S1192), and the cell dropped well under the method-count and size limits.

## Accessibility (colour-blind and dyslexic workers)

- Every button has a solid black edge and the same height (`.alloc-btn` in
  `rebuild/css/buttons-toolbar.css`).
- Meaning is carried by **Bootstrap Icons**, not emoji (emoji differ in size
  and colour between Windows and Android): door / tree / house for
  Front / Back / whole house; hourglass / flag / clock / stop-sign for
  allocated / completed / postponed / cancelled. The words live in tooltips
  and, visually hidden, for screen readers.
- Status icons are deliberately unlike the action icons (tick / pause /
  cross), so "this is done" is never mistaken for "mark it done".
- A postponed side shows *why* beside the clock (padlock, shield-cross,
  plane, question mark); the same icons are in the pause menu.
- Assigned and free chips differ by shape (filled / empty circle), not only
  colour.
- On touch screens (`@media (pointer: coarse)`) targets grow to 44px with
  wider gaps and the cancel is set apart from done and postpone. The crew
  form gets large black-edged tick boxes.
- Postpone is one Bootstrap dropdown: picking a reason submits the
  postponement in one step. The shared row dropdown no longer repeats
  `id="dropdownMenuButton"` in every row (`RowActionsDropdown`).

## Whole house versus Front and Back

A whole-house holder now shows as pressed on both the Front and Back lines.
Changing one side of an **open** whole house first splits it into the same
worker on both sides (`InvWorkerAllocationService::splitWholeHouse()`), so
the other side keeps its worker. Before, tapping a Front chip deleted the
whole-house row and the Back silently lost its worker. A **settled** whole
house is never rewritten; its derived chips are shown but disabled.

## No page reload (htmx)

- Each allocation form is an ordinary POST that htmx upgrades (`hx-post`,
  `hx-target="#alloc-<invId>"`, `hx-swap="outerHTML"`).
- `AllocationCellResponder` answers an `HX-Request` with just that
  invoice's re-rendered cell and no flash banner. Authorisation is
  unchanged: only the lead allocates, only the allocated worker or lead
  settles.
- Saving the crew changes every row's chips, so it returns `204` and
  `htmx-hooks.ts` re-fetches and swaps `#main-area` (the page-size pattern).
- Without htmx or JavaScript the forms still POST and redirect. A failed
  post reloads the page so a stale cell never looks right. Tooltips are
  disposed before a swap and re-initialised after it.
- Known limits: only the cell updates (other columns such as the send gate
  refresh on the next load), and two very fast taps can finish out of order.

## Verification

Testo (including `AllocationButtonsTest`, `AllocationCellResponderTest` and
the htmx branches of `HomeCareAllocationActionsTest`), Vitest
(`htmx-hooks.test.ts`), `tsc --noEmit` and full-project Psalm level 1 are all
clean, and the page has been clicked through in a real browser.
Rebuild the bundles with `npm run build:typescript` after pulling.
