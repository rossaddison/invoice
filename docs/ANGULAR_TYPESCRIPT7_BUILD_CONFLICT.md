# Angular Build Blocked by TypeScript 7 — Known Limitation

## Status: unresolved, documented, not blocking

`npm run build` / `make nb` fails at the final `build:angular` step on any
clean install. `build:css` and `build:typescript` (the two steps that
actually matter for the PHP/Yii3 app — see
[esbuild Scripts Broke on Linux](ESBUILD_LINUX_BINARY_FIX.md)) complete
successfully before it. This is a real, currently-accepted limitation, not
something to work around by editing generated files.

## Symptom

```
> yii3-i@1.0.0 build:angular
> ng build --configuration production

The "@angular-devkit/build-angular:browser" builder is deprecated as part
of Angular's Webpack support deprecation. Use "@angular/build:application"
instead.
An unhandled exception occurred: Cannot read properties of undefined
(reading 'Error')
See "<tmp>/angular-errors.log" for further details.
```

The error log points into `@angular/compiler-cli`'s `readConfiguration`,
called via `@angular-devkit/build-angular`'s webpack-based browser builder's
`readTsconfig` utility.

## Root cause

A hard peer-dependency version conflict:

```
@angular/compiler-cli@22.0.6 peer-requires: typescript ">=6.0 <6.1"
This project pins:                          typescript "^7.0.2"
```

(`package.json` also carries `@typescript/native-preview`, TypeScript's Go-based
native compiler preview — the `^7.0.2` pin is deliberate, not an accident,
presumably for the ES2024/esbuild toolchain used by `build:typescript`.)

TypeScript 7 restructured internal APIs that Angular 22's compiler-cli still
assumes have the TypeScript 6.0.x shape. `readConfiguration` reaches into
what it expects to be a `ts.DiagnosticCategory`-like object and gets
`undefined`, hence `Cannot read properties of undefined (reading 'Error')`.

## What was fixed vs what wasn't

A separate, genuine bug was found and fixed on the way to this one:
`@angular-devkit/build-angular` — the package that actually implements the
`:browser`/`:dev-server`/`:extract-i18n`/`:karma` builders `angular.json`
references — was **never declared** in `package.json` at all, despite being
required. Before the fix, `ng build` failed immediately with `Could not
find the '@angular-devkit/build-angular:browser' builder's node package`,
on a clean install on any OS (reproduced on Windows too, not Alpine-specific).
Added at `22.0.7` (exact version, matching this repo's convention of
pinning Angular packages exactly — see the `//dependency-note` comment in
`package.json`).

Fixing that declaration was necessary to even reach the TypeScript 7
conflict above. That conflict itself is **not** fixed — see options
considered below.

## Options considered (not yet acted on)

1. **Scope TypeScript 6.0.x to Angular only**, via an npm `overrides` entry
   targeting just `@angular/compiler-cli`'s internal `typescript` resolution,
   leaving the rest of the project (type-check, esbuild, native-preview) on
   TypeScript 7. Most surgical, but adds override complexity and hasn't been
   verified to actually work (Angular's webpack-based builder may resolve
   `typescript` from the hoisted root regardless of a scoped override).
2. **Downgrade the whole project to TypeScript ~6.0.x.** Simpler, but drops
   the deliberate TS 7 / native-preview setup project-wide — unknown blast
   radius on whatever TS 7 was pinned for.
3. **Leave it broken, document as a known limitation** (chosen). Angular
   integration is already flagged as fragile/WIP elsewhere in this repo
   (`package.json`'s own `//preinstall` warning: *"Do not run 'npm install'
   without reviewing Angular setup!"*). Revisit once Angular ships
   compiler-cli support for TypeScript 7, or if Angular becomes load-bearing
   enough to justify the override/downgrade investigation now.

## Practical workaround meantime

If you need the CSS + TypeScript bundles without the Angular step:

```bash
npm run build:css && npm run build:typescript
```

`make nb` / `npm run build` will keep failing at `build:angular` until one
of the options above is acted on.
