# Resizable Columns Rollout + SonarCloud Test-Classification Fix — August 2026

## Summary

Extended the drag-to-resize/auto-fit/reset column feature — originally
built for `inv/index` only — to the other five grids in the app:
`inv/guest`, `quote/index`, `quote/guest`, `salesorder/index`,
`salesorder/guest`. Along the way, merging the PR surfaced a real,
project-wide `sonar-project.properties` bug that had nothing to do with
this feature, and fixing it surfaced 4 more genuine SonarQube findings in
unrelated, pre-existing test files.

## The rollout

- **`src/typescript/column-resizer.ts`** (new) — the `ColumnResizer`
  class and its `initColumnResizer()` wiring helper, extracted out of
  `inv-index.ts` so every grid can opt in without duplicating the
  drag-resize/auto-fit/reset logic. `inv-index.ts`, `quote-index.ts`, and
  a new `salesorder-index.ts` all delegate to it now.
- **Per-page PHP wiring**: `->columnGrouping(true)` (renders the
  `<colgroup>` the resizer needs) + the shared `resizable-grid` CSS class
  + the 📐 auto-fit / 🔄 reset toolbar buttons, added to `inv/guest.php`,
  `quote/guest.php`, `salesorder/guest.php`, and the three already-
  `columnGrouping(true)` list widgets (`InvsListWidget`,
  `QuotesListWidget`, `SalesOrdersListWidget`).
- **Bug found along the way**: `salesorder/guest.php`'s GridView table id
  was hardcoded to `'table-quote'` — a copy-paste artifact from
  `quote/guest.php` that would have bled column-resize state between the
  two unrelated pages via a shared localStorage key. Renamed to
  `table-salesorder-guest`.
- **Test coverage**: `ColumnResizer`'s 47 tests moved out of
  `inv-index.test.ts` into their own `column-resizer.test.ts` (testing
  the shared module directly), plus new tests for `quote-index.ts`'s
  `tableId` parametrization and the new `salesorder-index.ts` module —
  no coverage lost.

## The SonarCloud detour

Pushing the rollout's PR (#1068) failed SonarCloud's quality gate on
`new_coverage` — 33.5%, then 43.3% after adding more tests, both well
under the required 80%. Digging into the per-file coverage breakdown via
the SonarCloud API turned up the real cause: **`sonar-project.properties`
had never declared TypeScript `*.test.ts` files as test code** — only
`sonar.tests=Tests` (the PHP directory) was set. Every `.test.ts` file was
being scored as ordinary source code requiring its own coverage, which is
nonsensical (nothing tests a test) — confirmed pre-existing and
project-wide via the API: `inv-index.test.ts`, untouched by this PR,
already sat at "0% coverage, 111 uncovered lines" before any of this
started. It just never mattered until this PR added enough new lines
inside brand-new `*.test.ts` files to trip the new-code gate.

Fixed by declaring `src/typescript` as both a source *and* a test root
(TS tests are co-located next to what they cover, unlike PHP's separate
`Tests/` directory), with `sonar.test.inclusions=**/*.test.ts` splitting
which files in it count as tests vs. source — the standard SonarQube
pattern for co-located test files. `new_coverage` went to 84.0%
immediately after.

## The knock-on findings (PR #1072)

Once `*.test.ts` files were correctly classified as tests, SonarQube's
test-specific rule set applied to them for the first time — surfacing
findings that had been latent in pre-existing, unrelated test files the
whole time:

- **BLOCKER `typescript:S2699`** — `cron.test.ts`'s "does not throw when
  clipboard is unavailable" test had no assertion at all. Given a real
  one: the generated key still lands in the input regardless of clipboard
  availability, and the button falls back to the repeat icon instead of
  the clipboard success checkmark — both genuinely exercised by
  `cron.ts`'s existing fallback path.
- **`typescript:S5976` ×2** — 3 structurally identical tests each in
  `phone-e164.test.ts` and `allowance-charge-toggle.test.ts` (same shape:
  build DOM, dispatch one event, assert one field) consolidated into a
  single `it.each` per file. Same number of individual test executions
  before and after.
- **`typescript:S5906`** — one more `.length).toBe(1)` → `toHaveLength(1)`
  swap in `inv-index.test.ts`, matching the pattern already fixed
  elsewhere in #1068.

## Verification

- `npx tsc --noEmit`: clean throughout.
- Vitest: 176/176 passing after the rollout; unchanged after the
  SonarQube cleanup (same total individual test executions — the S5976
  fixes only changed how tests are declared, not how many run).
- Full-project Psalm: no errors on every touched PHP file.
- Full PHPUnit suite: 3,907/3,907 passing, notice-free.
- Full Testo suite: 828/828 passing.
- SonarCloud quality gate: `OK` on both PRs — #1068's `new_coverage`
  84.0% (vs. 80% required), #1072's `new_code_smells` 0.

Both merged to `main`: #1068 (rollout + config fix), #1072 (the 4
knock-on findings).
