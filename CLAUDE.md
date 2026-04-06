# CLAUDE.md — rossaddison/invoice

This file provides persistent context for Claude when reviewing pull requests,
assisting with issues, and suggesting code improvements in this repository.

---

## Project Overview

**invoice** is a professional open-source e-invoicing system built with:
- **PHP** (primary language)
- **Yii3** framework
- **UBL 2.1** (Universal Business Language) standard
- **Peppol** e-invoicing network support
- **PHPUnit** for testing (see `Tests/` directory)
- **Angular** frontend (see `angular/` directory)

---

## Primary Goal for Claude in This Repo

### Reduce SonarQube String Duplication

The main task Claude assists with is **identifying and reducing duplicated string
literals** to help bring SonarQube's duplication metrics down.

When reviewing PHP files, Claude should:

1. **Flag string literals appearing 3+ times** — across files or within a single file
2. **Suggest PHP constants** using `UPPER_SNAKE_CASE` (PSR-1 compliant), e.g.:
   ```php
   // Before
   $status = 'active';
   // After
   const STATUS_ACTIVE = 'active';
   ```
3. **Suggest class constants or enums** where strings belong to a domain concept
   (e.g. invoice statuses, Peppol codes, UBL element names)
4. **Flag repeated UBL/Peppol string identifiers** — these are prime candidates
   for a dedicated constants file (e.g. `src/Invoice/UblConstants.php`)
5. **Flag repeated translation/label keys** hardcoded in multiple places
6. **Flag repeated SQL fragments or table/column name strings**

---

## Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| PHP constants | `UPPER_SNAKE_CASE` | `INVOICE_STATUS_DRAFT` |
| Class constants | `UPPER_SNAKE_CASE` | `self::PEPPOL_SCHEME_ID` |
| PHP enums (PHP 8.1+) | PascalCase name, string values lowercase | `InvoiceStatus::Draft` |
| Methods | `camelCase` | `getInvoiceTotal()` |
| Classes | `PascalCase` | `InvoiceRepository` |

---

## Where to Define Constants

| String Type | Suggested Location |
|-------------|-------------------|
| Invoice statuses | `src/Invoice/InvoiceStatus.php` (enum or constants class) |
| UBL element names / namespaces | `src/Invoice/Ubl/UblConstants.php` |
| Peppol codes / scheme IDs | `src/Invoice/Peppol/PeppolConstants.php` |
| General app-wide strings | `src/Invoice/AppConstants.php` |
| Database table/column names | Within the relevant repository or entity class |

---

## What Claude Should NOT Flag

- Single-character strings (`'/'`, `','`, `' '`)
- Standard HTML tags (`'<br>'`, `'<div>'`)
- PHP magic strings that are framework-required (e.g. Yii3 DI container keys)
- Strings that are intentionally repeated in test files for test clarity
- Strings appearing only twice (SonarQube threshold is typically 3+)

---

## Code Style

- Follows **PSR-1** and **PSR-12**
- PHP 8.1+ features are acceptable (enums, readonly properties, named arguments)
- Yii3 dependency injection patterns should be preserved
- Do not suggest changes that break UBL 2.1 or Peppol compliance

---

## Useful Context

- Invoice-related domain strings (statuses, types, codes) are central to the codebase
- UBL and Peppol specifications require precise string values — any constant
  extraction must preserve the exact string value
- The `Tests/` directory uses PHPUnit; test strings should not be extracted unless
  truly duplicated across production code as well
