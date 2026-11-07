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
- Known limit: only the cell updates (other columns such as the send gate
  refresh on the next load).
- **Fast taps are serialised per cell** (`alloc-queue.ts`): while one request for a
  house's cell is in flight, later taps on that cell are held and replayed in the order
  tapped once its reply has been swapped in. Otherwise two quick taps were parallel
  requests: the server could finish them in either order (the last write wins), and the
  second tap's reply landed in a cell the first reply had already replaced, so the screen
  stayed stale. htmx's own `hx-sync` queue cannot do this, because every tap swaps the
  cell it came from out of the page and htmx will not issue a queued request whose element
  is gone. Different houses never wait for each other, a lock older than 20 seconds is
  ignored so a lost reply cannot freeze a cell, and a tap that cannot be replayed is let
  through rather than dropped. Checked in a real browser (Chromium + real htmx): without
  it the second tap's result was lost from the screen; with it the server handled A then B
  and the screen ended on B, three runs in a row.

## Admin override on inv/index

An admin (anyone with `edit.inv`) can do everything the lead and the workers can,
from the staff `inv/index` grid. With HomeCare on, the **Worker** column shows the
same per-side cell instead of the single whole-house dropdown (an observer, who
lacks `edit.inv`, keeps the plain column):

- the crew chips (or, with no crew saved, the side + worker form) to allocate or
  clear any side, including splitting a whole house;
- the done / postpone / cancel buttons on **any** open side, whoever holds it;
  an admin's settlement is recorded as the office (`decided_by_worker_id` null,
  as the run sheet does) via `InvWorkerAllocationService::decideAsOffice()`;
- the "Today's crew" form above the grid, to set or override the crew and the
  Front/Back mode.

The same no-reload htmx taps apply. Every admin form carries a hidden
`context=index`, so the server answers with, and redirects to, the admin page
(`inv/index`) rather than the worker's `inv/guest`. That field only chooses the
page: the permission to act is checked separately (`edit.inv` or lead for
allocating and the crew; the allocated worker, the lead or `edit.inv` for
settling), so forging it gains nothing. A lead who is also an admin still settles
under their own worker id when they post from `inv/guest`.

## QR scan as the safety net for a forgotten completion

The customer's QR scan (`/scan/{token}`) used to do one thing: generate a new Sent
invoice. That ignored a planned draft the lead had already allocated for the same
visit, so a scan could bill the visit twice. It now handles that case:

- **GET /scan/{token}** (a scan, a link preview, anything that merely loads the
  page) has no side effect. If the client is an established customer (at least one
  paid invoice) and has a **planned draft** (dated today or earlier, newest first)
  with a side still waiting (status Allocated), the page asks "Has it been cleaned?"
  and shows the visit date and nothing else about the account.
- **POST /scan/{token}** (the customer's "Yes, it has been cleaned" button, with a
  CSRF token) claims today's visit slot, then
  `HomeCareScanConfirmer::confirm()` completes every waiting side, refreshes the send
  gate and marks the invoice **Sent without emailing anyone**
  (`InvMarkSentService::markSentWithoutEmail()`, which also writes the sent log).
  **No second invoice is generated** for that visit. The visit is recorded with
  outcome `confirmed`, which, like `generated`, is terminal: a repeat press or a second
  device the same day is a no-op.
- **No planned draft waiting:** the scan behaves exactly as before and generates the
  invoice. Both paths respect `homecare_auto_invoice_enabled` and the per-client pause.
- **The completion is labelled.** A side completed this way has
  `decision_source = customer_scan` (new nullable column on `inv_worker_allocation`),
  no worker, and shows a QR icon and "Confirmed by the customer (QR scan)" in its
  tooltip on `inv/guest` and `inv/index`, so it is never mistaken for a crew member's
  work. Sides a worker already completed, and postponed or cancelled sides, are left
  untouched. Re-allocating a side clears the label.
- Only the **newest** pending draft is confirmed, and a future-dated draft (a later
  run copied ahead) never is.

Deploying this needs the schema sync for the new column: set `BUILD_DATABASE=true`,
reload once, then set it back (or `rm runtime/schema.php` as in the deploy checklist).

## Carding: evidence the house was cleaned

The crew is getting into the habit of leaving a card at every house as evidence it
was cleaned. Each side now records it:

- A **card button** (a postcard icon, the same black-edged, equal-height size as the
  others) sits first on a side's controls, before done / postpone / cancel. One tap
  records `carded_at` (new nullable datetime on `inv_worker_allocation`). It posts in
  place with htmx like the other controls (`POST /inv/cardallocation/{allocation_id}`).
- It is offered on a **waiting or a completed** side, so a worker who forgot can card
  it after marking it done. It is not offered on a postponed or cancelled side (that
  visit was not cleaned), nor once a card is recorded.
- A carded side's status pill shows a postcard icon, and its tooltip says "Card left at
  the house". A completed side without the icon is the nudge: no card recorded yet.
- Who may record it is the same as for settling: the allocated worker, the lead, or an
  admin (`edit.inv`, from `inv/index`). Carding twice keeps the first time. Re-allocating
  a side clears the card.
- It is a record, not a gate: an uncarded side can still be completed and invoiced.

The new column needs the schema sync on deploy (`BUILD_DATABASE=true`, reload once).

## Shorter grid on a phone

In Front/Back mode a house with nothing allocated (the usual state first thing) has two
lines of crew chips, about 90px of a phone screen per house. Those two lines are now
folded into one native `<details class="alloc-collapsible">` whose summary shows the two
sides' pictures and a "person +" icon (same black-edged, equal-height style):

- It is rendered **open**, so it works with no script and is fully open on a desktop,
  where one-tap allocation matters most.
- On a phone-width screen (below Bootstrap's `md`, 768px) `alloc-collapse.ts` closes it, on
  page load, after a cell is swapped (only that cell, so a house the lead expanded
  elsewhere stays open) and after a grid refresh. A house then takes one line until the
  lead taps it.
- Only the "nothing allocated" case folds. A house with any allocation, a whole-house
  holder, a lone empty side, and whole-house mode (one line already) are left as they are.

## Verification

Testo (including `AllocationButtonsTest`, `AllocationCellResponderTest` and
the htmx branches of `HomeCareAllocationActionsTest`), Vitest
(`htmx-hooks.test.ts`), `tsc --noEmit` and full-project Psalm level 1 are all
clean, and the page has been clicked through in a real browser. The QR confirmation was also exercised end to end against the local site (GET changes nothing, POST without CSRF is rejected, POST completes the sides, marks the invoice sent with no email, and records the visit; repeat presses are no-ops).
Rebuild the bundles with `npm run build:typescript` after pulling.
