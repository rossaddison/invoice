@echo off
echo Building TypeScript for Invoice Application...

echo Checking TypeScript installation...
node -p "require('typescript').version" 2>nul || (
    echo TypeScript not found. Please install: npm install typescript
    exit /b 1
)

echo Compiling TypeScript files...
node_modules\.bin\tsc.cmd --project tsconfig.json --outDir temp_build

echo Bundling with simple concatenation...
type temp_build\types.js > src\Invoice\Asset\rebuild\js\invoice-typescript-compiled.js
type temp_build\utils.js >> src\Invoice\Asset\rebuild\js\invoice-typescript-compiled.js
type temp_build\create-credit.js >> src\Invoice\Asset\rebuild\js\invoice-typescript-compiled.js
type temp_build\index.js >> src\Invoice\Asset\rebuild\js\invoice-typescript-compiled.js

echo Cleaning up...
rmdir /s /q temp_build

echo Build complete! Output: src\Invoice\Asset\rebuild\js\invoice-typescript-compiled.js
echo.
echo To use: Update InvoiceAsset.php to load 'rebuild/js/invoice-typescript-compiled.js'
echo.
pause