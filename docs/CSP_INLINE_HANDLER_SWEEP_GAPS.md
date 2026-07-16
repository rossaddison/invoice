# CSP Inline-Handler Sweep Gaps — Second Wave

Follow-up to the original CSP hardening
([docs/CONTENT_SECURITY_POLICY_UPDATES.md](CONTENT_SECURITY_POLICY_UPDATES.md),
[docs/SECURITY_HARDENING_AUDIT_JULY_2026.md](SECURITY_HARDENING_AUDIT_JULY_2026.md)).
That sweep removed `'unsafe-inline'` from `script-src` and moved ~15 inline
`<script>`/`onclick`/`hx-on:` blocks into `src/typescript/*.ts`. It missed a
whole other category of inline handler because it searched for literal
`<script`/`onclick=` text — which doesn't match PHP code that builds the
same attribute via an array: `->addAttributes(['onclick' => '...'])`.

## Symptom

Reported as two things that had "lost functionality": `inv/index`'s column
filter dropdowns, and (a false lead — see below) the amount-hover
magnifier. Browser console showed:

```
Executing inline event handler violates the following Content Security
Policy directive 'script-src 'self' ...'. Either the 'unsafe-inline'
keyword, a hash, or a nonce is required to enable inline execution... The
action has been blocked.
```

The amount-hover magnifier (`AmountMagnifier` in `list-utils.ts`) was
already wired correctly via `addEventListener`, not an inline attribute —
unaffected by this bug, confirmed working independently.

## Full inventory found (17 instances, 12 files)

| Pattern | Count | Files |
|---|---|---|
| Vendor `DropdownFilter` renders `<select onChange="this.form.submit()">` | every dropdown filter column | `vendor/yiisoft/yii-dataview` — not app code, can't be swept by grepping this repo |
| `'onclick' => 'toggleGroupRows(this)'` (group-row collapse) | 3 | `InvsGroupingHelper.php`, `QuotesGroupingHelper.php`, `SalesOrdersGroupingRenderer.php` |
| `'onclick' => 'toggleAllGroups(true/false)'` (toolbar expand/collapse-all) | 5 | `InvsToolbar.php` ×2, `QuotesToolbar.php` ×2, `SalesOrdersListWidget.php` ×1 |
| `'onclick' => 'return confirm(...)'` (delete-button confirmation) | 5 | `InvsColumnBuilder.php`, `QuotesColumnBuilder.php`, `GeneratorListWidget.php`, `FamilyListWidget.php`, `ProductsListWidget.php` |
| `'onclick' => 'this.showPicker()'` (date input) | 2 | `FormFields.php`, `InvsToolbar.php` |
| `'onclick' => 'window.history.back()'` | 2 | `Button.php` ×2 |

The vendor `DropdownFilter` case is the one that broke the reported
`inv/index` filters — it renders its inline `onChange` directly, outside
this repo's control, so no text search over app source could ever have
caught it.

## Fix

Reused and extended the existing `data-action`/`data-confirm` delegation
infrastructure in `src/typescript/data-actions.ts` (already handled
`show-picker`, `window-close`, `window-print`, `toggle-commalist-picker`,
`data-confirm`) rather than inventing a new mechanism:

- **Dropdown filters**: added a delegated `change` listener for
  `select.native-reset` (the class every `DropdownFilter::widget()` call
  applies, confirmed universal across Inv/Quote/SalesOrder/Product) that
  calls `.form.submit()`. Fixes the vendor-rendered inline handler without
  touching vendor code — the vendor's own broken `onChange` attribute is
  still there and still silently blocked by CSP, harmlessly; this listener
  does the same job independently.
- **Group-row collapse**: added a delegated `click` listener for
  `.group-header` inside `initGroupCollapsible()` (`list-utils.ts`), guarded
  by a `globalThis` flag so repeated calls (e.g. across test cases sharing
  one jsdom `document`) don't stack duplicate listeners — the first attempt
  at this without the guard caused a real test failure (two listeners firing
  on one click cancels the toggle out).
- **Toolbar expand/collapse-all, history-back**: new `data-action` cases
  (`toggle-all-groups` with a `data-expand="true"/"false"` companion
  attribute, `history-back`) added to the existing switch in
  `initDataActions()`.
- **Delete confirmation**: converted every `'onclick' => 'return
  confirm(...)'` to `'data-confirm' => $t->translate(...)`, already handled
  by `initDataActions()`'s existing `[data-confirm]` branch — as a side
  effect this also fixed a latent bug in 4 of the 5 sites, which built the
  confirm message via raw string concatenation (`"return confirm('" .
  $t->translate(...) . "');"`) instead of `json_encode`, meaning a
  translated string containing an apostrophe would have produced broken
  JavaScript; `data-confirm` sidesteps this entirely since it's a normal
  HTML attribute value, not hand-built JS source.
- **showPicker**: converted to the existing `data-action="show-picker"`
  case, already implemented.

All 17 `'onclick' =>` occurrences in `src/` are gone; `git grep` confirms
zero remaining.

## Tests added

- `src/typescript/data-actions.test.ts` (new file — this module had zero
  test coverage despite loading on every page): covers all six
  `data-action` cases, both `data-confirm` outcomes (accept/cancel), and
  both the positive and negative `select.native-reset` change-submit paths.
- `src/typescript/list-utils.test.ts`: three new cases for the
  `.group-header` click delegation (direct hit, `closest()` bubbling from a
  child element, click elsewhere is a no-op).

Full suite: 131/131 passing (was 116 before this fix).

## Known follow-up gap

Neither Vitest/jsdom nor the existing Codeception `Acceptance` suite
(`PhpBrowser` — no JS execution) can catch this *class* of bug, because
neither enforces a real CSP header against real rendered JavaScript. A
real-browser test (Playwright, or Codeception + `module-webdriver`) would
be needed to catch a future regression of this kind automatically — not
set up as part of this fix, flagged as a separate follow-up decision.

## Also discovered, not fixed here

`npm run type-check` (`tsc --noEmit`) and `eslint` both fail outright on
this repo's `tsconfig.json` — same root cause as
[docs/ANGULAR_TYPESCRIPT7_BUILD_CONFLICT.md](ANGULAR_TYPESCRIPT7_BUILD_CONFLICT.md):
TypeScript 7 removed the `moduleResolution=node10`/`baseUrl` options this
config uses, and `@typescript-eslint/typescript-estree` crashes trying to
build a type-aware Program against TS 7's restructured internals. Doesn't
block this fix — `esbuild` (the actual production bundler) and `vitest`
don't depend on `tsc`'s type-checking machinery — but broadens the known
TS7-incompatibility scope beyond just Angular.
