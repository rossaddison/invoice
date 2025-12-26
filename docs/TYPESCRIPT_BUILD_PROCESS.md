# TypeScript Build Process Documentation

This document explains the complete sequence of events to generate the `invoice-typescript-iife.js` file from TypeScript sources in the Invoice application.

## Overview

The Invoice application uses a modern TypeScript-based build system that compiles multiple TypeScript modules into a single, optimized JavaScript IIFE (Immediately Invoked Function Expression) file. This approach provides better maintainability, type safety, and performance compared to loading individual JavaScript files.

## Project Structure

### TypeScript Source Files (`src/typescript/`)

```
src/typescript/
├── index.ts          # Main entry point & application initialization
├── utils.ts          # Utility functions & API helpers
├── types.ts          # TypeScript type definitions
├── scripts.ts        # Common UI scripts (tooltips, selects, etc.)
├── create-credit.ts  # Create credit functionality
├── quote.ts          # Quote handling & operations
├── client.ts         # Client management & forms
├── invoice.ts        # Invoice operations & workflows
├── product.ts        # Product management + product lookup modals
├── tasks.ts          # Task lookup functionality
├── salesorder.ts     # Sales order handling
├── family.ts         # Family category management
├── settings.ts       # Settings configuration
└── cron.ts          # Cron functionality (standalone, not bundled)
```

### Build Configuration Files

- **`package.json`** - Contains build scripts and dependencies
- **`tsconfig.json`** - TypeScript compiler configuration
- **`esbuild`** - Fast JavaScript bundler (used instead of webpack)

## Build Process Sequence

### 1. Entry Point Resolution

**Command Execution:**
```bash
npm run build:prod
# Executes: esbuild src/typescript/index.ts --bundle --outfile=src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js --target=es2020 --format=iife --global-name=InvoiceApp --minify
```

**Entry Point:** `src/typescript/index.ts`

### 2. Dependency Graph Construction

The build system analyzes `index.ts` and recursively resolves all imports:

```typescript
// Main imports in index.ts
import { CreateCreditHandler } from './create-credit.js';
import { QuoteHandler } from './quote.js';
import { ClientHandler } from './client.js';
import { InvoiceHandler } from './invoice.js';
import { ProductHandler } from './product.js';      // includes product lookups
import { TaskHandler } from './tasks.js';           // includes task lookups
import { SalesOrderHandler } from './salesorder.js';
import { FamilyHandler } from './family.js';
import { SettingsHandler } from './settings.js';
import { initTooltips, initSimpleSelects, showFullpageLoader, 
         hideFullpageLoader, initPasswordMeter } from './scripts.js';
```

**Dependency Chain:**
- Each handler imports utilities from `utils.ts`
- Type definitions are imported from `types.ts`
- Cross-dependencies are resolved automatically

### 3. Module Resolution & Bundling

**esbuild Process:**
- Traverses all imports recursively
- Resolves TypeScript files (`.ts` → `.js` extensions in imports)
- Bundles all dependencies into a single file
- Performs tree-shaking to eliminate unused code
- Resolves circular dependencies

### 4. TypeScript Compilation

**Compilation Settings (tsconfig.json):**
```json
{
  "compilerOptions": {
    "target": "ES2020",           // Modern JavaScript features
    "module": "ESNext",           # Latest module system
    "moduleResolution": "node",   # Node.js-style resolution
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "strict": true,               # Strict type checking
    "esModuleInterop": true,
    "skipLibCheck": true
  }
}
```

**Compilation Steps:**
1. Type checking across all files
2. Transform TypeScript features to JavaScript
3. Convert to ES2020 target
4. Maintain source location mapping

### 5. IIFE Format Generation

**Output Structure:**
```javascript
var InvoiceApp = (function() {
    'use strict';
    
    // Bundled utility functions
    function parsedata(data) { /* ... */ }
    function getJson(url, params) { /* ... */ }
    
    // Handler classes
    var CreateCreditHandler = class { /* ... */ };
    var QuoteHandler = class { /* ... */ };
    var ClientHandler = class { /* ... */ };
    var InvoiceHandler = class { /* ... */ };
    var ProductHandler = class { /* ... */ };
    var TaskHandler = class { /* ... */ };
    // ... other handlers
    
    // Main application class
    var InvoiceApp = class {
        constructor() {
            this._createCreditHandler = new CreateCreditHandler();
            this._quoteHandler = new QuoteHandler();
            this._clientHandler = new ClientHandler();
            this._invoiceHandler = new InvoiceHandler();
            this._productHandler = new ProductHandler();
            this._taskHandler = new TaskHandler();
            // ... initialize all handlers
            
            console.log('Invoice TypeScript App initialized with all core handlers');
        }
    };
    
    // Auto-initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new InvoiceApp());
    } else {
        new InvoiceApp();
    }
    
    return { InvoiceApp: InvoiceApp };
})();
```

### 6. Minification (Production Build)

**Production Optimizations:**
- Code minification and compression
- Variable name mangling (`longVariableName` → `a`)
- Dead code elimination
- Comment removal
- Whitespace removal

**Development vs Production:**
- **Development:** `npm run build:dev` - Includes source maps, no minification
- **Production:** `npm run build:prod` - Minified, optimized for size
- **Watch Mode:** `npm run build:watch` - Rebuilds on file changes

### 7. Output Generation

**Final Output:**
- **File:** `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js`
- **Current Size:** ~53KB (minified)
- **Format:** Self-executing function
- **Global Variable:** `InvoiceApp`
- **Browser Compatibility:** ES2020+ (modern browsers)

## Integration with PHP Application

### Asset Loading (InvoiceAsset.php)

```php
public array $js = [
    // Single TypeScript compiled bundle
    'rebuild/js/invoice-typescript-iife.js',
    
    // Disabled individual JS files (now in TypeScript bundle):
    //'rebuild/js/quote.js',
    //'rebuild/js/inv.js',
    //'rebuild/js/salesorder.js',
    //'rebuild/js/client.js',
    //'rebuild/js/family.js',
    //'rebuild/js/product.js',
    //'rebuild/js/setting.js',
    //'rebuild/js/scripts.js',
    //'rebuild/js/modal-product-lookups.js',
    //'rebuild/js/modal-task-lookups-inv.js',
    
    // Standalone files (not in TypeScript bundle):
    'rebuild/js/cron.js',                        // Intentionally separate
    'rebuild/js/emailtemplate.js',               // No TypeScript equivalent
    'rebuild/js/mailer_ajax_email_addresses.js', // No TypeScript equivalent
];
```

### Runtime Initialization

The IIFE executes immediately when the script loads:

1. **Page Load:** Browser loads `invoice-typescript-iife.js`
2. **IIFE Execution:** Function executes immediately
3. **DOM Ready Check:** Waits for DOM if still loading
4. **App Initialization:** Creates `InvoiceApp` instance
5. **Handler Setup:** All event listeners are bound
6. **Global Access:** `InvoiceApp` available globally

## Build Commands

### NPM Scripts

```bash
# Production build (minified)
npm run build:prod

# Development build (with source maps)
npm run build:dev

# Watch mode (rebuilds on file changes)
npm run build:watch

# Type checking only (no compilation)
npm run type-check

# Linting
npm run lint

# Formatting
npm run format
```

### Menu System Integration

**Makefile Commands:**
```bash
make tsb    # TypeScript Build (Production)
make tsd    # TypeScript Development Build  
make tsw    # TypeScript Watch Mode
make tst    # TypeScript Type Check
make tsl    # TypeScript Lint
make tsf    # TypeScript Format
```

**Windows Batch (m.bat) Commands:**
```
[4d] TypeScript Build (Production)
[4e] TypeScript Development Build
[4f] TypeScript Watch Mode  
[4g] TypeScript Type Check
[4h] TypeScript Lint
[4i] TypeScript Format
```

## Functionality Included in Bundle

### Core Handlers
- **Quote Handler:** Quote creation, editing, PDF generation, email sending
- **Invoice Handler:** Invoice operations, payments, status management
- **Client Handler:** Client management, notes, form handling
- **Product Handler:** Product management + product lookup modals
- **Task Handler:** Task lookup functionality for invoices
- **Sales Order Handler:** Sales order operations and conversions
- **Family Handler:** Product family/category management
- **Settings Handler:** Application configuration
- **Create Credit Handler:** Credit note functionality

### Modal Functionality
- **Product Lookups:** Select products for quotes/invoices
- **Task Lookups:** Select tasks for invoices
- **Form Handling:** Secure form submission and validation
- **AJAX Operations:** API communication with backend

### UI Components
- **Tooltips:** Bootstrap tooltip initialization
- **Select Components:** TomSelect initialization
- **Password Meter:** Password strength validation
- **Fullpage Loader:** Loading state management

## Excluded Functionality

### Standalone Files (Not in Bundle)
- **Cron (`cron.js`):** Cron key generation (intentionally separate)
- **Email Templates (`emailtemplate.js`):** Email template functionality
- **Mailer AJAX (`mailer_ajax_email_addresses.js`):** Email address handling

### TypeScript Files Not Bundled
- **`cron.ts`:** Exists but not imported (standalone JS version used)

## Development Workflow

### Making Changes

1. **Edit TypeScript Files:** Modify files in `src/typescript/`
2. **Type Check:** Run `npm run type-check` to verify types
3. **Build:** Run `npm run build:prod` to generate new bundle
4. **Test:** Verify functionality in browser
5. **Deploy:** The generated IIFE file is ready for production

### Debugging

1. **Development Build:** Use `npm run build:dev` for source maps
2. **Watch Mode:** Use `npm run build:watch` for automatic rebuilds
3. **Browser DevTools:** Source maps allow debugging original TypeScript
4. **Console Logging:** All handlers log initialization messages

## Performance Benefits

### Before (Individual Files)
- **Files:** 10+ separate JavaScript files
- **HTTP Requests:** Multiple requests per page
- **Caching:** Individual file caching
- **Load Time:** Slower due to multiple requests

### After (Single IIFE Bundle)
- **Files:** 1 optimized JavaScript file
- **HTTP Requests:** Single request
- **Caching:** Single file caching
- **Load Time:** Faster initial load
- **Size:** ~53KB minified (tree-shaken)

## Troubleshooting

### Common Issues

1. **Build Errors:** Check TypeScript syntax and imports
2. **Missing Functionality:** Verify handler is imported in `index.ts`
3. **Runtime Errors:** Check browser console for initialization issues
4. **Type Errors:** Run `npm run type-check` before building

### Build Diagnostics

```bash
# Check build output size and timing
npm run build:prod

# Verify TypeScript compilation
npm run type-check

# Check for linting issues
npm run lint

# View file timestamps
Get-Item "src\Invoice\Asset\rebuild\js\invoice-typescript-iife.js" | Select-Object Name, Length, LastWriteTime
```

## Future Enhancements

### Planned Improvements
- **Code Splitting:** Split bundle for better caching
- **Lazy Loading:** Load handlers on demand
- **Source Maps:** Production source maps for debugging

### Migration Path
- **Cron Integration:** Consider moving `cron.js` to TypeScript bundle
- **Email Templates:** Migrate `emailtemplate.js` to TypeScript
- **Additional Modals:** Add more modal functionality to TypeScript

---

**Last Updated:** November 5, 2025  
**Bundle Version:** invoice-typescript-iife.js (53KB)  
**TypeScript Version:** 5.9.3  
**Build Tool:** esbuild 0.25.0