# CLAUDE.md — rossaddison/invoice

This file provides persistent context for Claude when reviewing pull requests,
assisting with issues, and suggesting code improvements in this repository.

---

## Project Overview

**invoice** is a professional open-source e-invoicing system built with:
- **PHP** (primary language)
- **Yii3** framework
- **UBL 2.4** (Universal Business Language) standard
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
- PHP 8.4+ is the minimum (enums, readonly properties, named arguments, property hooks, asymmetric visibility)
- Yii3 dependency injection patterns should be preserved
- Do not suggest changes that break UBL 2.4 or Peppol compliance

---

## Sensitive Data — Never Commit Real People's Data

On 2026-11-11 a bulk `git add -A` pushed customer run documents to a public branch. These rules exist
so it cannot happen again; they apply to every commit, push and pull request.

- **Never** run `git add -A`, `git add .` or `git commit -a`. Stage files by explicit path, then read
  `git diff --cached --name-only` before every commit and again before every push. Stop if anything
  under `imports/`, or any `.docx`, `.xlsx`, `.csv`, database, archive or `.env` file, is in the list.
- Customer documents (`imports/`) are read locally for verification only. Scratch files, outputs and
  scripts that touch real data go outside the repository, never into a folder that is committed.
- Tests, fixtures and docs use **virtual** data only: emails on `example.test`/`example.com`,
  telephone numbers in `01632 960xxx`, postcodes starting `ZZ`, surnames starting `Virtual`/`Test`.
  To turn a real file into a fixture run
  `php bin/virtualize-sensitive-test-data-check.php virtualize <input> <output>` and then **read the
  output**: street names and other free text are not detected. See
  `App\Invoice\Testing\VirtualizeSensitiveTestDataCheck` and
  `docs/SENSITIVE_TEST_DATA_GUARD_NOVEMBER_2026.md`.
- Never put real customers, streets, areas or company names in code, annotations, tests, docs, commit
  messages or pull request descriptions.
- The pre-commit hook, the pre-push hook and the `Sensitive data guard` CI job refuse these cases.
  Never bypass them (`--no-verify`, disabling hooks). If a legitimate file is refused, say so and add it
  to `.githooks/sensitive-allowlist` in a reviewed commit with a reason.
- If real data is committed or pushed anyway: stop, tell the user immediately and plainly, and push
  nothing further. Deleting a branch does **not** remove it: GitHub keeps a pull request's commits
  reachable under `refs/pull/<number>/head` until GitHub support removes them.
- Never make a repository public, force-push, or delete remote refs without the user's explicit approval.

---

## DDD Infrastructure Migration

Entities in `src/Invoice/Entity/` are being replaced by infrastructure persistence
classes in `src/Infrastructure/Persistence/{Name}/{Name}.php` (Cycle ORM).

### Definition of Done — per entity

A migration step is only complete when **all** of the following are true:

- [ ] Old `src/Invoice/Entity/{Name}.php` deleted
- [ ] `src/Infrastructure/Persistence/{Name}/{Name}.php` created with Cycle ORM attributes
- [ ] All controllers, services, repositories, forms updated to use the new FQCN
- [ ] View `@var` annotations updated to the infrastructure FQCN
- [ ] PHPUnit test file exists at `Tests/Unit/Invoice/Entity/{Name}EntityTest.php`
- [ ] Test file uses infrastructure FQCN (not old entity)
- [ ] `reqId(): int` pattern used — never `getId(): string`; `isPersisted()` tested
- [ ] Test file passes Psalm at errorLevel 1 (run directly: `vendor/bin/psalm --no-cache Tests/Unit/Invoice/Entity/{Name}EntityTest.php`)

### Key API differences (old entity → new infrastructure class)

| Old | New |
|-----|-----|
| `new Entity($id, ...)` | `new Entity(...); $e->setId($id);` |
| `getId(): string` | `reqId(): int` (throws `\LogicException` when unpersisted) |
| _(no method)_ | `isPersisted(): bool` |
| `assertSame('N', $e->getId())` | `assertSame(N, $e->reqId())` |
| `assertIsString($e->getId())` | `assertIsInt($e->reqId())` |
| `assertSame('', $e->getId())` | `assertFalse($e->isPersisted())` |

### Old Codeception tests (`Tests/Unit/*EntityTest.php`)

When an entity is deleted, any matching Codeception test at `Tests/Unit/` must also
be updated: swap the `use` import, replace positional-id constructor args with
`setId()`, and replace all `getId()` assertions with `reqId()` int equivalents.

### Test quality standard

Every test marker other than `.` (pass) is a failure:
- `S` skipped, `I` incomplete, `R` risky, `D` deprecation, `N` notice, `W` warning
- Never use `markTestSkipped()` as a workaround — mock the dependency instead
- A `createMock()` used purely as a stub (configured with `willReturn()`/`willReturnMap()`
  but never asserted with `expects()`) triggers PHPUnit 13's stub-without-expectations
  notice — resolve with `#[AllowMockObjectsWithoutExpectations]` (class-level when every
  method in the file is affected, otherwise per-method), not by leaving the notice in
  place. As of August 2026 the suite is notice-free (`OK (3824 tests, ...)`, zero `N`
  markers) — do not reintroduce this without fixing it the same way.

---

## Useful Context

- Invoice-related domain strings (statuses, types, codes) are central to the codebase
- UBL and Peppol specifications require precise string values — any constant
  extraction must preserve the exact string value
- The `Tests/` directory uses PHPUnit; test strings should not be extracted unless
  truly duplicated across production code as well
