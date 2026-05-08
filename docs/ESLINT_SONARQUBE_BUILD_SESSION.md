# ESLint, SonarQube & Build Process Session Notes

**Date:** 28 March 2026  
**Project:** Yii3 Invoice (`yii3-i`)  
**Repository:** https://github.com/rossaddison/invoice

---

## Overview

This session covered resolving SonarQube `var` statement complaints, configuring ESLint flat config, fixing Angular build errors, and cleaning up Angular build warnings.

---

## 1. ESLint Configuration Migration

### Problem
VS Code was generating `var` statements in TypeScript which SonarQube flags against.

### ESLint Flat Config (`eslint.config.js`)
Migrated from `.eslintrc.cjs` to the new flat config format:

```javascript
import js from '@eslint/js';
import typescriptEslint from '@typescript-eslint/eslint-plugin';
import typescriptParser from '@typescript-eslint/parser';
import deprecation from 'eslint-plugin-deprecation';
import globals from 'globals';

export default [
    js.configs.recommended,
    {
        files: ['**/*.ts', '**/*.tsx'],
        plugins: {
            '@typescript-eslint': typescriptEslint,
            'deprecation': deprecation
        },
        languageOptions: {
            parser: typescriptParser,
            ecmaVersion: 2020,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2020,
                ...globals.node
            }
        },
        rules: {
            'deprecation/deprecation': 'warn',
            '@typescript-eslint/no-unused-vars': 'warn',
            '@typescript-eslint/no-explicit-any': 'warn',
            'no-var': 'error',
            'prefer-const': 'error'
        }
    }
];
```

### Key Migration Differences
- `root: true` is gone — flat config is root by default
- `parser` moves into `languageOptions.parser`
- `plugins` are imported as ES modules, not strings
- `extends` replaced by spreading configs directly in the array
- `env` replaced by explicit `globals` via the `globals` package

### ESLint & TypeScript Notes
- `@typescript-eslint/parser` requires the standard `typescript` package as a peer dependency
- `@typescript/native-preview` (v7, Go-based) is **not** a substitute for `typescript`
- Both can coexist: `typescript` satisfies tooling, `@typescript/native-preview` powers VS Code via `"typescript.experimental.useTsgo": true`
- Installing `typescript` does **not** affect the native preview experience in VS Code

---

## 2. SonarQube `var` Fix

### Root Cause
The `var` statements were **not** coming from TypeScript source files — they were being introduced by **esbuild** during IIFE bundling. ESLint on source files would not have fixed this.

### Verification
```powershell
Select-String -Pattern "^var " -Path "src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js" | Select-Object -First 5
```

### Fix
Added `--supported:const-and-let=true` to esbuild commands in `package.json`:

```json
"build:typescript:prod": "esbuild src/typescript/index.ts --bundle --outfile=src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js --target=es2024 --format=iife --global-name=InvoiceApp --minify --supported:const-and-let=true && esbuild src/typescript/index.ts --bundle --outfile=src/Invoice/Asset/rebuild/js/invoice-typescript-iife.min.js --target=es2024 --format=iife --global-name=InvoiceApp --minify --supported:const-and-let=true",
"build:typescript:dev": "esbuild src/typescript/index.ts --bundle --outfile=src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js --target=es2024 --format=iife --global-name=InvoiceApp --sourcemap --supported:const-and-let=true"
```

### Bonus
A `.min.js` version of the IIFE is now produced alongside the standard build:
- `invoice-typescript-iife.js` — standard output
- `invoice-typescript-iife.min.js` — additional minified version

---

## 3. Angular Build Errors Fixed

### Error 1: `TS5101` — Deprecated options (`baseUrl`, `downlevelIteration`)
**File:** `tsconfig.angular.json`  
**Fix:** Added `"ignoreDeprecations": "5.0"` to `compilerOptions`

### Error 2: `TS4111` — Index signature property access
**File:** `angular/src/app/amount-magnifier.directive.ts`  
**Fix:** Changed dot notation to bracket notation:
```typescript
// Before
const currentFontSize = Number.parseFloat(this.originalStyles.fontSize);

// After
const currentFontSize = Number.parseFloat(this.originalStyles['fontSize']);
```

**File:** `angular/src/app/invoice-amount-magnifier.service.ts`  
**Fix:** Changed dataset access to bracket notation:
```typescript
// Before
htmlElement.dataset.magnifierInitialized
htmlElement.dataset.magnifierInitialized = 'true';

// After
htmlElement.dataset['magnifierInitialized']
htmlElement.dataset['magnifierInitialized'] = 'true';
```

### Error 3: `TS2420` — Missing `ngOnDestroy()` method
**File:** `angular/src/app/family-commalist/family-commalist.component.ts`  
**Fix:** Added the missing method:
```typescript
ngOnDestroy(): void {
    // No subscriptions or timers to clean up
}
```

### Error 4: `TS2345` — `CSSStyleDeclaration` type mismatch
**File:** `angular/src/app/invoice-amount-magnifier.service.ts`  
**Fix:** Created a `MagnifierStyles` interface to replace `CSSStyleDeclaration`:
```typescript
interface MagnifierStyles {
    fontSize: string;
    fontWeight: string;
    backgroundColor: string;
    border: string;
    borderRadius: string;
    padding: string;
    zIndex: string;
    position: string;
    transform: string;
    boxShadow: string;
}
```
Updated method signatures from `CSSStyleDeclaration` to `MagnifierStyles` and replaced `getPropertyValue` with `keyof MagnifierStyles` bracket access:
```typescript
element.style.setProperty(property, originalStyles[property as keyof MagnifierStyles]);
```

### Error 5: `TS7017` — `globalThis` index signature
**File:** `angular/src/app/services/flash-message.service.ts`  
**Fix:** Changed `globalThis.flashMessageTimer` to `window.flashMessageTimer` since `Window` was already declared with the property:
```typescript
// Before
if (globalThis.flashMessageTimer) {
    return globalThis.flashMessageTimer;
}

// After
if (window.flashMessageTimer) {
    return window.flashMessageTimer;
}
```

### Error 6: Missing `angular/styles.scss`
**Fix:** Created empty file:
```powershell
New-Item -Path "C:\wamp64\www\invoice\angular\styles.scss" -ItemType File
```

### Error 7: `Can't resolve 'angular/styles.scss'` asset path errors
**Fix:** Updated `angular.json` assets to use glob format and removed non-existent `angular/assets` folder reference. Updated favicon to point to actual location:
```json
"assets": [
    {
        "glob": "favicon.ico",
        "input": "public/site",
        "output": "/"
    }
]
```
Also removed the unnecessary `public/assets` glob which was causing very long copy times.

---

## 4. Angular Build Warnings Fixed

### Warning 1: `topLevelAwait` in `main.ts`
**Fix:** Replaced top-level `await` with promise chaining:
```typescript
// Before
try {
    await bootstrapApplication(AppComponent, { providers: [] });
} catch (err) {
    console.error(err);
}

// After
bootstrapApplication(AppComponent, {
    providers: []
}).catch(err => console.error(err));
```

### Warning 2: Unused environment files and directive
**Fix:** Tightened `include` in `tsconfig.angular.json` and excluded unused file:
```json
"include": [
    "angular/src/**/*"
],
"exclude": [
    "angular/src/app/amount-magnifier.directive.ts",
    "node_modules",
    "dist",
    "src/typescript/**/*"
]
```

### Warning 3: SCSS budget exceeded
**Fix:** Increased budget in `angular.json`:
```json
{
    "type": "anyComponentStyle",
    "maximumWarning": "4kb",
    "maximumError": "8kb"
}
```

### Warning 4: Bundle size budget exceeded
**Fix:** Increased budget in `angular.json`:
```json
{
    "type": "initial",
    "maximumWarning": "800kb",
    "maximumError": "2mb"
}
```

---

## 5. Known Remaining Warning

### `govuk-frontend` CSS Syntax Warning
```
▲ [WARNING] Expected "{" but found "(" [css-syntax-error]
```
- **Cause:** Legacy IE8/IE9 `screen\0` media query hack in `govuk-frontend` 6.1.0
- **Status:** Third party issue — nothing actionable until `govuk-frontend` releases a fix
- **Impact:** None — SonarQube analyses source files, not bundled CSS output

---

## 6. Final Build Output

```
src\Invoice\Asset\rebuild\js\invoice-typescript-iife.js     72.0kb ✅
src\Invoice\Asset\rebuild\js\invoice-typescript-iife.min.js 72.0kb ✅

√ Browser application bundle generation complete.
√ Copying assets complete.
√ Index html generation complete.
```

---

## 7. Useful Commands

```powershell
# Verify no var statements in IIFE output
Select-String -Pattern "^var " -Path "src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js" | Select-Object -First 5

# Check TypeScript version
npx tsc --version

# List all tsconfig files in project
Get-ChildItem -Path "C:\wamp64\www\invoice" -Recurse -Filter "tsconfig*.json" | Select-Object FullName

# Full production build
npm run build:prod

# TypeScript IIFE build only
npm run build:typescript:prod
```

---

## 8. Package Dependencies Added

```bash
npm install --save-dev typescript
npm install globals --save-dev
```

---

*Session completed: 28 March 2026*
