# 🚀 NetBeans IDE 25 Integration Guide
*Enhanced workflow for collaborative development - November 2025*

## 🎯 **Quick Start**
```bash
# Open NetBeans IDE 25
.\open-netbeans.bat

# Check file sync
.\sync-check.bat "src\Invoice\Asset\InvoiceAsset.php"

# Monitor changes in real-time
.\monitor-files.bat
```

---

## 🏗️ **NetBeans IDE 25 Features**

### **Enhanced PHP 8.4 Support**
- Full PHP 8.4 syntax highlighting
- Advanced type hints and annotations
- Property hooks and asymmetric visibility
- Improved autocompletion

### **Modern JavaScript/TypeScript**
- ES2024 module support
- TypeScript compilation integration
- Advanced debugging tools
- Real-time error detection

### **Project Structure Integration**
```
nbproject/
├── project.properties    ← Enhanced for IDE 25
├── project.xml
└── private/
    └── private.properties
```

---

## 🔄 **Sync Workflow with VS Code AI**

### **1. Before Making Changes**
```powershell
# Always check sync status
.\sync-check.bat "path\to\file.php"

# Start monitoring (in separate terminal)
.\monitor-files.bat
```

### **2. Development Process**
1. **NetBeans IDE 25**: Make your changes
2. **Save** (Ctrl+S) - auto-triggers sync
3. **AI Assistant**: Detects changes automatically
4. **Validate**: Check with sync-check.bat

### **3. Key Files to Monitor**
- `src/Invoice/Asset/InvoiceAsset.php` ← JavaScript loading config
- `src/typescript/settings.ts` ← Form handling logic
- `src/Widget/Button.php` ← Button generation
- `src/Controller/*` ← Business logic

---

## 🛠️ **NetBeans IDE 25 Specific Configuration**

### **Project Properties Enhanced**
```properties
# PHP 8.4 Support
php.version=PHP_84
source.encoding=UTF-8

# TypeScript Integration
auxiliary.org-netbeans-modules-typescript.project.include.paths=src/typescript
auxiliary.org-netbeans-modules-typescript.build.output=src/Invoice/Asset/rebuild/js

# Auto-reload for sync
auxiliary.org-netbeans-modules-php-project.auto-reload=true
```

### **IDE 25 Features to Use**
- **Code Templates**: Create custom PHP/TypeScript snippets
- **Refactoring Tools**: Safe rename across files
- **Git Integration**: Built-in version control
- **Debugger**: XDebug integration for PHP
- **Terminal**: Built-in PowerShell support

---

## 🚨 **Critical Sync Points**

### **JavaScript Asset Loading**
```php
// In InvoiceAsset.php - THIS IS CRITICAL
// Always use compiled bundle, not individual files
$this->css[] = $this->sourceUrl . '/rebuild/css/invoice.css';
$this->js[] = $this->sourceUrl . '/rebuild/js/invoice-typescript-iife.js';
```

### **Form Submission Issues**
If forms stop working:
1. Check `InvoiceAsset.php` is using compiled bundle
2. Verify TypeScript compiled correctly: `npm run build`
3. Check browser console for JavaScript errors
4. Use `debug-forms.php` for detailed debugging

---

## 🔧 **Troubleshooting**

### **NetBeans Won't Open Project**
```powershell
# Check installation
dir "C:\Program Files\NetBeans*"

# Manually open
"C:\Program Files\NetBeans-25\netbeans\bin\netbeans64.exe" --open "C:\wamp64\www\invoice"
```

### **Sync Issues**
```powershell
# Verify file exists
.\sync-check.bat "problematic\file.php"

# Check real-time monitoring
.\monitor-files.bat

# Force refresh NetBeans
# File → Reload Project (Ctrl+Shift+R)
```

### **TypeScript Not Compiling**
```powershell
# Check Node.js/npm
node --version
npm --version

# Rebuild TypeScript
npm run build

# Verify output
dir "src\Invoice\Asset\rebuild\js\"
```

---

## 💡 **Best Practices for IDE 25**

### **File Organization**
- Use NetBeans **Projects** window for navigation
- Bookmark frequently edited files (Ctrl+Shift+M)
- Use **Go to File** (Ctrl+Shift+O) for quick access

### **Code Quality**
- Enable **Real-time error detection**
- Use **Code Analysis** tools (Alt+Shift+I)
- Run **Code Templates** for consistent formatting

### **Performance**
- Increase heap size if needed: `netbeans.conf`
- Use **Background Scanning** for large projects
- Enable **Fast Open** for quick file access

---

## 📋 **Daily Workflow Checklist**

- [ ] Start NetBeans IDE 25 with `.\open-netbeans.bat`
- [ ] Start file monitoring with `.\monitor-files.bat`
- [ ] Check critical file sync before major changes
- [ ] Save frequently (auto-sync enabled)
- [ ] Test forms after JavaScript/PHP changes
- [ ] Use sync-check.bat for validation
- [ ] Communicate changes to AI assistant

---

## 🎯 **Success Indicators**

✅ **NetBeans IDE 25 opens project correctly**  
✅ **File monitoring detects changes instantly**  
✅ **Forms submit without JavaScript errors**  
✅ **TypeScript compiles to bundle successfully**  
✅ **Sync scripts work without errors**  
✅ **AI assistant receives change notifications**

---

*🤝 Perfect sync between NetBeans IDE 25 and VS Code AI Assistant!*