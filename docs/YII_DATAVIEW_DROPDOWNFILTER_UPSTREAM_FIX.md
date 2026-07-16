# yii-dataview DropdownFilter CSP Bug — Reported and Fixed Upstream

Follow-up to [CSP Inline-Handler Sweep Gaps](CSP_INLINE_HANDLER_SWEEP_GAPS.md),
which found the root cause of the `inv/index` filter-dropdown regression:
`vendor/yiisoft/yii-dataview`'s `DropdownFilter` widget renders an inline
`onChange="this.form.submit()"` attribute that a strict `script-src` (no
`unsafe-inline`) silently blocks. That doc covers the app-side workaround
(a delegated `change` listener in `src/typescript/data-actions.ts`
targeting `select.native-reset`). This doc covers reporting and fixing the
bug at its source, since a vendor package bug affects every consumer of
`yii-dataview`, not just this app.

## Issue filed

[yiisoft/yii-dataview#344](https://github.com/yiisoft/yii-dataview/issues/344)
— root cause, exact file/line (`src/Filter/Widget/DropdownFilter.php:127-131`,
package version `1.1.0`), the real console CSP violation text, and the
production-impact story (filters silently breaking with no exception
anywhere to catch).

## Pull request filed

[yiisoft/yii-dataview#345](https://github.com/yiisoft/yii-dataview/pull/345)
— adds `DropdownFilter::submitOnChange(bool $enabled): self`, an immutable
fluent method matching the class's existing API (`addAttributes()`,
`attributes()`, `addClass()`, `class()`). Defaults to `true`: existing
output is byte-identical for every consumer not calling the new method,
purely additive, non-breaking. `DropdownFilter` is `final`, so before this
fix there was no way for a CSP-strict consumer to work around the inline
handler short of forking the package.

Usage once merged:

```php
DropdownFilter::widget()
    ->addAttributes(['class' => 'my-filter-select'])
    ->submitOnChange(false)
    ->optionsData($options);
```

```js
document.addEventListener('change', (e) => {
    if (e.target.matches('select.my-filter-select')) {
        e.target.form?.submit();
    }
});
```

## Verification before opening the PR

Branched directly off `upstream/master` rather than the `rossaddison/yii-dataview`
fork's own `master`, which turned out to be a long-stale, pre-1.0-era
divergent branch with an entirely different directory layout — sidestepped
reconciling that entirely rather than risking a destructive sync.

| Check | Result |
|---|---|
| `phpunit` (full suite) | 514/514 passing |
| `phpunit --filter DropdownFilterTest` | 23/23 (18 existing + 5 new) |
| `psalm --no-cache` | No errors |
| `php-cs-fixer --dry-run --diff` | 0 files need fixing |
| `rector process --dry-run` | No changes suggested |

Added 3 new test cases (disabled → no `onChange` rendered; explicitly
re-enabled; combined with `addAttributes()`) plus extended the existing
`testImmutability()`. Added a `CHANGELOG.md` entry under `1.1.1 under
development`, matching the project's format. Diff scoped to exactly 3
files, 81 insertions.

## Status / follow-up

This app keeps its own `data-actions.ts` delegated-listener workaround
regardless of whether/when the upstream PR merges — it works today, isn't
contingent on an upstream release, and (per
[CSP_INLINE_HANDLER_SWEEP_GAPS.md](CSP_INLINE_HANDLER_SWEEP_GAPS.md)) also
covers other inline-handler regressions the upstream fix doesn't touch
(group-row collapse, delete-confirm, etc.). If/when yii-dataview releases
a version with `submitOnChange()`, `InvsColumnBuilder.php` (and the Quote/
SalesOrder/Product equivalents) could optionally call
`->submitOnChange(false)` explicitly for clarity, but it's not required —
the CSP already blocks the vendor's inline handler either way, and our own
listener does the job independently.
