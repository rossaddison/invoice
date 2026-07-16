# esbuild Scripts Broke on Linux — `node <path>` vs Calling the Binary

## Symptom

`npm run build`, `make nb`, or the `.githooks/pre-commit` TypeScript rebuild
failed on a fresh Alpine Linux install (though the bug is not Alpine-specific
— any Linux or macOS machine hits it) with:

```
/var/www/invoice/node_modules/esbuild/bin/esbuild:1
ELF>`...
^

SyntaxError: Invalid or unexpected token
    at wrapSafe (node:internal/modules/cjs/loader:1787:18)
    ...
Node.js v24.16.0
make: *** [Makefile:368: nb] Error 1
```

The same commands worked fine on Windows.

## Root cause

`package.json`'s `build:typescript:dev` / `:auth` / `:prod` scripts invoked
esbuild as:

```
node node_modules/esbuild/bin/esbuild src/typescript/index.ts --bundle ...
```

`esbuild`'s own `postinstall` script (`install.js`) has a POSIX-only
optimisation: once it confirms the platform-specific binary package
(e.g. `@esbuild/linux-x64`) installed correctly, it **overwrites**
`node_modules/esbuild/bin/esbuild` in place with the real compiled native
binary, replacing the small JS dispatcher shipped in the package — faster,
since the OS can exec it directly without spawning Node twice.

Windows can't do that swap (a `.exe` can't masquerade as something
`node <file>` still parses as JavaScript), so on Windows `bin/esbuild`
stays as the original JS wrapper and `node node_modules/esbuild/bin/esbuild`
happens to work. On Linux/macOS, that same command feeds a raw ELF/Mach-O
binary to Node's JS parser — hence `Invalid or unexpected token`.

## Fix

Drop the `node` prefix and call the binary name directly. npm automatically
puts `node_modules/.bin` on `PATH` for any script run via `npm run`, and
that directory has the correct shim on every OS — a `.cmd` wrapper on
Windows, a direct executable/symlink on POSIX:

```diff
- "build:typescript:dev": "node node_modules/esbuild/bin/esbuild src/typescript/index.ts --bundle ...",
+ "build:typescript:dev": "esbuild src/typescript/index.ts --bundle ...",
```

Applied to all three esbuild-invoking scripts in `package.json`
(`build:typescript:dev`, `build:typescript:auth`, `build:typescript:prod`).
`.githooks/pre-commit` already calls these through `npm run
build:typescript:prod` / `npm run build:typescript:auth` rather than
invoking esbuild directly, so no separate hook fix was needed.

## Verification

Rebuilt on Windows after the change — identical output, same bundle sizes,
no diff in the generated `.js`/`.min.js` files:

```
$ npm run build:typescript:prod
  src\Invoice\Asset\rebuild\js\invoice-typescript-iife.js      156.2kb
  src\Invoice\Asset\rebuild\js\invoice-typescript-iife.min.js  156.2kb
```

On the Alpine box, `git pull && make nb` should now get past the point
where it previously failed.

## Takeaway

Never hardcode `node node_modules/<pkg>/bin/<tool>` in an npm script for a
package that ships a native binary (esbuild, swc, turbo, etc.) — always
call the bin name directly and let npm's `.bin` shim resolve it per
platform.
