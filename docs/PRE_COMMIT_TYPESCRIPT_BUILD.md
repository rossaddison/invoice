# Pre-commit TypeScript IIFE Build Hook

## Problem

Every TypeScript source change requires the compiled IIFE bundle to be rebuilt
before committing. Without automation, the bundle in
`src/Invoice/Asset/rebuild/js/` can silently fall behind the source files,
shipping stale JavaScript to production.

## Solution

A Git pre-commit hook in `.githooks/pre-commit` rebuilds both IIFE bundles
automatically before every commit and stages the output files so they are
always included in the same commit as the source change.

## How it works

```
git commit  →  .githooks/pre-commit fires
                 │
                 ├─ npm run build:typescript:prod  (≈ 20 ms via esbuild)
                 │   ├─ invoice-typescript-iife.js      (142 KB)
                 │   └─ invoice-typescript-iife.min.js  (142 KB)
                 │
                 ├─ git add both output files
                 │
                 └─ commit proceeds (or aborts if build fails)
```

If the build fails the commit is blocked until the TypeScript error is fixed.

## Files

| File | Purpose |
|------|---------|
| `.githooks/pre-commit` | Hook script — rebuild + stage |
| `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js` | Production bundle |
| `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.min.js` | Minified bundle |

## Setup for a fresh clone

The `prepare` script in `package.json` runs automatically after `npm install`
and points Git at the committed hooks directory:

```bash
npm install          # triggers prepare → git config core.hooksPath .githooks
```

No further manual steps are needed.

## Why `node node_modules/esbuild/bin/esbuild`

The `esbuild` platform binary is present in `node_modules/esbuild/bin/esbuild`
but is not linked into `node_modules/.bin/` on this Windows environment, so the
bare `esbuild` command is not found by the shell. Calling the binary through
`node` directly is reliable on all platforms and requires no PATH changes.

## Watch mode (active development)

For iterative development, run option `[4k]` in `m.bat` or:

```bash
npm run build:typescript:watch
```

This rebuilds the bundle on every `.ts` file save. The pre-commit hook still
runs at commit time to guarantee the final bundle is up to date.

## m.bat integration

| Option | Command | When to use |
|--------|---------|-------------|
| `[4i]` | `npm run build:prod` | One-shot production build |
| `[4j]` | `npm run build:dev` | One-shot development build (with source maps) |
| `[4k]` | `npm run build:watch` | Continuous rebuild during development |
