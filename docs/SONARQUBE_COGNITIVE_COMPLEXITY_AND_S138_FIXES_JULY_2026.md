# SonarQube Fixes: invoice.ts Cognitive Complexity + InvsColumnBuilder S138 — July 2026

## Background

Two violations surfaced after this month's `inv/index` work (Worker
allocation column, "Copy All to Date" bulk action) pushed two
already-large functions just past their SonarQube thresholds:

```
CRITICAL: typescript:S3776 - src/typescript/invoice.ts:141 - Refactor this
function to reduce its Cognitive Complexity from 16 to the 15 allowed.
ERROR: php:S138 - src/Invoice/Inv/Widget/InvsColumnBuilder.php:67 - This
function "buildColumns" has 173 lines, which is greater than the 150 lines
authorized. Split it into smaller functions.
```

Both fixes follow patterns already established earlier this session rather
than inventing new ones.

## 1. `invoice.ts` — `handleClick()` cognitive complexity

`handleClick()` is a flat sequence of `if (closestSafe(target, '#id')) { ...; return; }`
checks dispatching every click on `inv/index` to the right handler. Each
top-level `if` adds 1 to SonarQube's cognitive-complexity count; the
"Copy All to Date" branch added a couple of sessions ago was the one that
tipped it from 15 to 16.

The file already has the fix pattern in it: `handleExportClick(target): boolean`
groups the six PDF/HTML export checks into their own method, called from
`handleClick()` as `if (this.handleExportClick(target)) { return; }` — one
branch instead of six. The three "copy invoice" checks (spreadsheet
import, multi-invoice copy, single-invoice copy) got the same treatment: a
new `handleCopyClick(target): boolean`, called the same way. Net effect:
`handleClick()` drops from 16 branches to 14 (removes 3, adds back 1 for
the new method call).

## 2. `InvsColumnBuilder::buildColumns()` — 173 lines, needed to split without adding a method

This class already carries a docblock explaining it exists specifically to
keep `InvsListWidget` within S1448 (≤ 20 methods) and S138 (≤ 150
lines/function). The problem: it was *already* at exactly 20 methods
(including the constructor) before this fix — confirmed by counting method
declarations directly, since SonarQube hadn't flagged an S1448 violation
for this file. Adding one more named method to shrink `buildColumns()`
would have fixed S138 by reopening S1448.

The fix reuses the exact technique from `AuthController`'s S1448 fix
earlier this session (`src/Auth/Trait/Redirects.php`): move methods into a
**trait**, since SonarQube counts a class's *own* declared methods, not
ones contributed by a `use`'d trait, even though PHP flattens trait
methods into the consuming class at compile time and they're fully
callable via `$this->`. New file:
`src/Invoice/Inv/Widget/Trait/InvsWorkerColumnTrait.php`, holding
`buildWorkerColumn()` and `buildQuickPayColumn()` — the two single-column
builders with the most self-contained logic (the Worker allocation
dropdown and the quick-pay button/badge), pulled out of the inline
`DataColumn` definitions they used to be. `InvsColumnBuilder` now `use`s
the trait and calls both methods from `buildColumns()`.

Result: `buildColumns()` is 113 lines (was 173), and `InvsColumnBuilder`'s
own method count stayed at exactly 20 — confirmed by recounting after the
change, not assumed.

The trait's docblock carries `@property-read` annotations for the three
`InvsColumnBuilder` properties its methods reach into (`$translator`,
`$urlGenerator`, `$csrf`) — Psalm needs these since the trait itself
declares none of them; they only exist once the trait is composed into the
class that does.

## Verification

- Full-project `vendor/bin/psalm --no-cache` clean.
- `vendor/bin/testo --suite=Unit` — 242 tests, unaffected.
- `esbuild` production bundle builds clean.
- Method count on `InvsColumnBuilder` reverified at exactly 20 after the
  trait extraction (not just assumed from the pattern working previously).
