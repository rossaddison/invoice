# Removed the Parked Angular Scaffold — August 2026

## Summary

Deleted the `angular/` folder, `angular.json`, `tsconfig.angular.json`,
`tsconfig.spec.json`, and every `@angular*`/`rxjs`/`zone.js`/`tslib`
dependency from `package.json`. This was prompted by an `npm audit` report
of 4 high-severity vulnerabilities, but the deeper reason to remove rather
than patch was that the scaffold had no live purpose left.

## Why removal, not a version bump

`npm audit` traced two distinct issues to `@angular-devkit/build-angular`'s
own dependency tree:

- `nanoid <3.3.17` (via `postcss`) — fixed safely with a plain `npm audit
  fix`, no breaking change, independent of the rest of this cleanup.
- `image-size` (via `less`) — DoS in ICNS/JXL/HEIF parsing. The only fix
  npm offered was `npm audit fix --force`, which bumps
  `@angular-devkit/build-angular` to `0.1002.1`/22.x and pulls in whatever
  breaking changes come with that.

Patching a breaking upgrade is only worth it for code that actually runs.
This scaffold didn't: `angular/` had real source files and a real
`angular.json` build config, but was never built (no `dist/` output
checked in or produced by any tracked script path), never registered as an
asset bundle anywhere Yii3 actually serves from, and its one real feature
(an invoice-amount magnifier directive) had already been reimplemented in
plain TypeScript — see `docs/INVOICE_AMOUNT_MAGNIFIER.md`. Historical docs
(`docs/ESLINT_SONARQUBE_BUILD_SESSION.md`,
`docs/ANGULAR_TYPESCRIPT7_BUILD_CONFLICT.md`,
`docs/PEPPOL_SCHEMATRON_CODEGEN.md`) still reference `angular/src/...`
paths from when the scaffold existed — left as-is, since they're accurate
records of what happened at the time, not living documentation.

Given that, removing the scaffold fixed both vulnerabilities at once,
instead of accepting a breaking upgrade for code nobody runs.

## What changed

- Deleted `angular/` (18 tracked files), `angular.json`,
  `tsconfig.angular.json`, `tsconfig.spec.json` (Angular's own Jasmine/Karma
  test config, which only extended `tsconfig.angular.json` and only
  included `angular/**` — dead the moment the scaffold was).
- `package.json`: removed `@angular/compiler` from `dependencies`; removed
  every `@angular-devkit/*`, `@angular-eslint/*`, `@angular/*` package,
  plus `rxjs`, `zone.js`, `tslib` (confirmed unused anywhere outside the
  scaffold) from `devDependencies`; removed the `build:angular`,
  `build:angular:dev`, `ng`, `ng:build`, `ng:serve`, `ng:test`, `ng:lint`,
  `angular:setup`, `angular:generate-component`,
  `angular:generate-service`, `lint:angular`, `clean:angular` scripts;
  simplified `build:all`/`build:dev`/`build:prod` to no longer chain an
  Angular build step; removed the `@angular/compiler-cli` entry from
  `overrides` (only existed to pin its own `@babel/core` peer); removed
  the Angular setup warning comments at the top of `scripts`.
- `npm install` then removed 709 packages from `node_modules` and
  `package-lock.json`.
- `npm audit fix` cleared the remaining `nanoid` advisory.

## Verification

- `npm audit`: **0 vulnerabilities** (down from 4 high).
- `npm run build:css` and `npm run build:typescript:prod`: both still
  build cleanly — the real esbuild-based pipeline never depended on
  Angular's toolchain.
- `npm run type-check`: clean.
- `npm run test` (vitest): 143/143 passing, 9/9 test files — none were
  Angular tests.
- `npm run lint` fails with `typescript-eslint does not support TS 7.0` —
  pre-existing, unrelated to this change (see the recent TypeScript 7
  globalThis/Window typing fix commits); not something this removal
  caused or could fix.
