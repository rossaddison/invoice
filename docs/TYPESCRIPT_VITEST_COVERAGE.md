# TypeScript Vitest Coverage

**May 2026**

## Problem

SonarCloud was reporting large numbers of uncovered lines in the TypeScript
source modules despite the code being exercised at runtime:

| File | Uncovered lines |
|------|----------------|
| `src/typescript/inv-index.ts` | 72 |
| `src/typescript/list-utils.ts` | 70 |
| `src/typescript/quote-index.ts` | 9 |

No TypeScript test runner existed in the project. All PHP coverage was also
missing from CI because `phpunit.xml.dist` pointed to the wrong directory
on Linux (case-sensitive filesystem).

---

## What Was Done

### 1. Vitest test runner added

`vitest`, `@vitest/coverage-v8`, and `jsdom` added to `devDependencies`
in `package.json`. Three npm scripts added:

```json
"test":          "vitest run",
"test:watch":    "vitest",
"test:coverage": "vitest run --coverage"
```

### 2. `vitest.config.ts` created

```typescript
import { defineConfig } from 'vitest/config';

export default defineConfig({
    resolve: {
        // TypeScript source files resolve when imports use .js extensions
        extensionAlias: { '.js': ['.ts', '.js'] },
    },
    test: {
        environment: 'jsdom',
        include: ['src/typescript/**/*.test.ts'],
        coverage: {
            provider: 'v8',
            include: ['src/typescript/**/*.ts'],
            exclude: ['src/typescript/**/*.test.ts', 'src/typescript/index.ts'],
            reporter: ['lcov', 'text'],
            reportsDirectory: 'coverage',
        },
    },
});
```

Key points:
- `extensionAlias` allows `import './list-utils.js'` to resolve to the
  `.ts` source file during tests.
- `lcov` reporter generates `coverage/lcov.info` which SonarCloud reads
  via `sonar.javascript.lcov.reportPaths`.

### 3. Test files written

| Test file | Tests | What it covers |
|-----------|-------|---------------|
| `src/typescript/inv-index.test.ts` | 21 | `MobilePreviewToggle` constructor, styles injection idempotency, activate/deactivate, collapse/restore, `watchPopup` interval, `initInvIndex` readyState branching, filter-config label hydration |
| `src/typescript/quote-index.test.ts` | 4 | `initQuoteIndex` readyState branching, group-header conditional |
| `src/typescript/list-utils.test.ts` | 27 | `AmountMagnifier` badge detection (`isAmount` all branches), `addBehavior` mouse and click events for all three badge colour variants, `setupObserver` with container / fallback / none, `MutationObserver` callback via fake timers, `initGroupBySelect` change handler, `initGroupCollapsible` row collapse/expand and `toggleAllGroups` |

### 4. Known jsdom behaviour to be aware of

**Hex colours are normalised to `rgb()` on read-back.**
When code does `el.style.border = '2px solid #28a745'`, reading
`el.style.border` back returns `'2px solid rgb(40, 167, 69)'`.
Assertions must use the `rgb()` form.

**`getComputedStyle` returns non-empty defaults for some properties.**
`border` has a default computed value in jsdom; checking it returns `''`
after `restore()` is unreliable. Use `borderRadius` instead — it is
`''` when unset and `'6px'` after `magnify()`.

**ES module named exports cannot be spied on at the call-site.**
`vi.spyOn(listUtils, 'initGroupCollapsible')` registers zero calls
because Vitest 4's module isolation means the bound import inside
`inv-index.ts` is not the same reference as the spy wrapper. Verify
call-site branch coverage by testing observable behaviour or by checking
DOM/global side-effects from the same module realm.

### 5. `phpunit.xml.dist` case fix

`phpunit.xml.dist` referenced `./tests/PHPUnit` (lowercase `t`). Git
tracked the directory as `Tests/PHPUnit` (uppercase `T`). On Linux CI
runners the filesystem is case-sensitive, so PHPUnit silently found no
test files and generated empty coverage data. Fix: one character change.

```xml
<!-- before -->
<directory>./tests/PHPUnit</directory>

<!-- after -->
<directory>./Tests/PHPUnit</directory>
```

### 6. Coverage wired into CI (`invoice_build.yml`)

The `sonar` job (which gates the matrix build via `needs: [sonar]`) was
extended to generate both coverage reports before the SonarCloud scan:

```
sonar job:
  1. PHP 8.4 + pcov  →  composer update  →  phpunit --coverage-clover coverage.xml
  2. Node.js 24      →  npm install       →  npm run test:coverage  (→ coverage/lcov.info)
  3. SonarCloud scan (reads both files)
```

`sonar-project.properties` declares both paths:

```properties
sonar.php.coverage.reportPaths=coverage.xml
sonar.javascript.lcov.reportPaths=coverage/lcov.info
```

Note: `sonar.javascript.lcov.reportPaths` is the correct key for
TypeScript — SonarCloud's TypeScript analyser is bundled inside the
JavaScript plugin and uses the same property.

### 7. SonarCloud coverage badge added to README

```markdown
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=rossaddison_invoice&metric=coverage)](https://sonarcloud.io/summary/new_code?id=rossaddison_invoice)
```

This is distinct from the existing Psalm type-coverage badge
(shepherd.dev) which tracks static type-inference completeness, not
test execution coverage.

---

## Final coverage after tests

| File | Statements | Branches | Functions | Lines |
|------|-----------|---------|-----------|-------|
| `inv-index.ts` | 98.8 % | 82.4 % | 100 % | 98.6 % |
| `list-utils.ts` | 100 % | 94.2 % | 100 % | 100 % |
| `quote-index.ts` | 87.5 % | 75 % | 100 % | 87.5 % |

The one line remaining uncovered in `inv-index.ts` (line 146) and
`quote-index.ts` (line 12) is the `initGroupCollapsible()` call-site.
The code IS executed during tests, but V8's coverage instrument does not
record cross-module call-sites in Vitest 4's isolated module environment.
The function body in `list-utils.ts` is correctly reported as 100 % covered.

## Related Docs

- [SonarCloud First Gate](SONARCLOUD_FIRST_GATE.md) — the workflow gate that
  blocks the matrix build until SonarCloud passes
- [Sonarcloud CLI](SONARCLOUD_CLI.md) — querying SonarCloud issues locally
- [SonarQube IDE Setup](SONARQUBE_IDE_SETUP.md) — VS Code Connected Mode
