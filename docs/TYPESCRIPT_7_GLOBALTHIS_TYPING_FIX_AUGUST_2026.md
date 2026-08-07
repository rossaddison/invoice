# TypeScript 7 — `globalThis` vs. `Window` Typing Fix — August 2026

## Summary

`npm run type-check` (`tsc --noEmit`) had been silently failing at the
**config-parsing stage** since a `composer`-adjacent `Node update` bumped
`typescript` to `^7.0.2` — meaning it never actually reached real
source-file checking for a while. Getting it working again (deliberately
staying on TypeScript 7 rather than downgrading — the native/Go port is a
real, wanted speed win) surfaced a genuine, long-suspected root cause: a
running conflict between this app's own convention of using `globalThis`
everywhere (never `window` — SonarQube `typescript:S7764`) and how its
ambient global types were actually declared.

## Part 1 — the config was never TS7-compatible

TypeScript 7 removed `moduleResolution: "node"` (the `"node10"` alias) and
`baseUrl` outright. `tsconfig.json` still used both, so `tsc` failed
before ever opening a single source file — `npm run type-check` reporting
success in practice (nothing ran) was worse than reporting failure.

Fixed by switching to `moduleResolution: "bundler"` — already the working
pattern in `tsconfig.sw.json` from the HomeCare PWA service worker, so
this was precedented, not a guess — and rewriting the one `paths` entry to
not depend on `baseUrl`:

```diff
-    "moduleResolution": "node",
+    "moduleResolution": "bundler",
     ...
-    "baseUrl": "./src/typescript",
     "paths": {
-      "@/*": ["*"]
+      "@/*": ["./src/typescript/*"]
     },
```

## Part 2 — the real conflict, once type-checking could actually run

With the config fixed, `tsc` reached real source files for what looks
like the first time in a while, and reported **~20 errors across 11
files** — all pre-existing, none introduced by anything recent.

The common thread in most of them: `declare global { interface Window { x:
T } }` augments `Window`'s type. It does **not** extend `globalThis`'s own
type the way it might seem like it should — TypeScript doesn't unify the
two for this purpose. Since every one of this app's own globals is read
and written via `globalThis.x` (correctly, per the app's own
`globalThis`-not-`window` rule), the *type declarations* were describing
the wrong object the whole time. It happened to not matter as long as
`tsc` was failing before it ever got far enough to check it.

Fixed everywhere by switching to the form that TypeScript **does**
propagate onto `globalThis`: `declare global { var x: T; }` — in
`htmx.ts`, `flash-message-timer.ts`, `index.ts`, `product.ts`, and
`family-commalist-picker.ts`.

(Why `var` specifically, not `const`/`let`: this mirrors real JavaScript
semantics — a top-level `var` at script scope becomes a property of the
global object; a top-level `const`/`let` does not. TypeScript's ambient
global declarations follow the same rule.)

## Knock-on findings

Fixing the mechanism surfaced a few genuine, independent bugs that had
simply never been checked before:

- **`types.ts` declared `bootstrap` twice** — once as `interface Window`,
  once as a separate `const` — and neither actually covered a plain
  `globalThis.bootstrap` read (the `const` form has the same "doesn't
  reach `globalThis`" problem as `let`). Merged into one `var bootstrap`,
  and added the `Tooltip.getOrCreateInstance` static-method type that
  `index.ts`'s own tooltip init already calls but was never actually
  type-checked against.
- **`TomSelect` was declared twice, conflictingly** — a specific
  constructor type in `product.ts`, a loose `any` in `types.ts`. Kept
  `product.ts`'s more accurate one, removed the duplicate.
- **`salesorder.ts`'s TomSelect-undefined guard didn't actually protect
  its own call** — `if (globalThis.TomSelect === undefined) return;`
  followed by `new globalThis.TomSelect(...)` inside a `.forEach()`
  callback. TypeScript's narrowing doesn't survive a function-boundary
  crossing like that; a real, if impractical-to-trigger, gap. Fixed by
  hoisting the checked value into a local `const` before the callback in
  both `product.ts` and `salesorder.ts`.
- **`family.ts`: `FamilyGenerateResponse.success` was typed `boolean`**
  where its own sibling interfaces (`FamilySecondaryResponse`,
  `FamilyNamesResponse`) and the base `ApiResponse` all correctly declare
  `0 | 1`, matching this app's actual PHP `json_encode` convention. Fixed
  to match, and tightened the one `if (data.success)` truthy check to
  `=== 1` for consistency with its sibling handlers (behaviorally
  identical for `0`/`1`, just no longer the odd one out).
- **`family-commalist-integration.ts` deleted** — a dead, zero-reference
  file (grep-confirmed unimported anywhere in the app), superseded by
  `family-commalist-picker.ts`'s plain-TypeScript reimplementation of the
  exact same `toggleCommalistPicker`/`picker` global API. The same
  "Angular scaffold superseded by plain TypeScript" pattern already
  documented elsewhere in this app.
- Two now-unnecessary `as any` casts removed (`index.ts`'s `bootstrap`
  read, `product.ts`'s `TomSelect` check) now that the underlying globals
  are properly typed.

## Verification

- `npm run type-check`: clean, zero errors, both `tsconfig.json` and
  `tsconfig.sw.json`.
- Vitest: 143/143 passing.
- `npm run build:typescript:prod`: clean, identical 281.8kb bundle size —
  every change here is type-only or a behavior-preserving local-`const`
  hoist, so esbuild's actual output (which strips all type-level code
  regardless) was never going to differ.
