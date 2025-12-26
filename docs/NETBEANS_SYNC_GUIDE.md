# NetBeans + VS Code AI Integration Guide

## 🔄 SYNC WORKFLOW

### Before Making Changes:
1. **Check File Sync**: Run `sync-check.bat "src\Widget\Button.php"`
2. **Monitor Changes**: Run `monitor-files.bat` in background
3. **Verify NetBeans sees same content** as AI assistant

### When AI Suggests Changes:
1. **Copy exact changes to NetBeans**
2. **Save in NetBeans first**
3. **Verify with sync-check script**
4. **Test the changes**

### For TypeScript Changes:
1. **Edit TypeScript files in NetBeans**
2. **Run**: `npm run build` 
3. **Update InvoiceAsset.php** if needed
4. **Clear browser cache**

## 📁 KEY FILES TO MONITOR:

- `src/Widget/Button.php` - Form button configurations
- `src/Invoice/Asset/InvoiceAsset.php` - JavaScript loading
- `src/typescript/settings.ts` - Settings form handling
- `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js` - Compiled JS

## 🔧 TROUBLESHOOTING:

### If Files Don't Match:
1. Check file timestamps with `sync-check.bat`
2. Refresh NetBeans project (F5)
3. Clear VS Code file cache
4. Verify working directory

### If Changes Don't Work:
1. Check which JS files are loaded (InvoiceAsset.php)
2. Verify TypeScript compilation (`npm run build`)
3. Clear browser cache (Ctrl+F5)
4. Check browser console for errors

## 🎯 COMMUNICATION PROTOCOL:

When reporting issues to AI:
1. **Specify you're using NetBeans**
2. **Share exact file content** from NetBeans
3. **Mention any differences** you see
4. **Confirm changes are applied** before testing

## ⚡ QUICK COMMANDS:

- `sync-check.bat "src\Widget\Button.php"` - Check file sync
- `monitor-files.bat` - Monitor key files
- `npm run build` - Compile TypeScript
- `open-netbeans.bat` - Launch NetBeans with project