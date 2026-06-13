# Peppol / EN16931 Validation Data

> **Not the same as `src/Invoice/Helpers/Peppol/DownloadedXml/`.**
> This folder feeds **validation** (flat membership lists consumed by `PeppolValidator`).
> `DownloadedXml/` feeds **UI dropdowns** (VEFA XML files with Id + Name + Description,
> loaded by `PeppolArrays` to populate `<select>` elements in forms).

## Purpose

Each `.php` file here returns a flat `string[]` array.  They are loaded lazily
by `CodeList::load(CodeLists $case)` the first time a given code is validated,
then held in a `private static $cache` for the remainder of the process.
PHP opcache compiles them to bytecode — no parsing overhead after the first hit.

`PEPPOL-EN16931-UBL.sch` is the Schematron rules file resolved by
`PeppolValidator` via `Aliases('@peppol' => resources/peppol)`.

## How the pipeline works

```
CodeLists (enum)          CodeList (class)           resources/peppol/
─────────────────         ─────────────────          ──────────────────
CodeLists::EAID  ──────►  load(CodeLists::EAID)  ──► eaid.php → ['0002','0007',…]
                           contains($schemeId)
```

`PeppolValidator` is the only production caller of `CodeList::contains()`.
The Schematron expression evaluator (`Ast/ExpressionEvaluator`, `Emit/PhpExpressionEmitter`)
also calls `CodeList::load()` when compiling `inCodeList()` predicates from the `.sch` file.

## Files

| File | `CodeLists` case | Validates | Peppol rule |
|------|-----------------|-----------|-------------|
| `eaid.php` | `EAID` | `cbc:EndpointID[@schemeID]` | PEPPOL-CL-0008 |
| `iso3166.php` | `ISO3166` | country codes in address fields | BR-CL-14 |
| `iso4217.php` | `ISO4217` | document and tax currency codes | BR-CL-04, BR-CL-05 |
| `mime.php` | `MIME` | MIME type of binary attachments | BR-CL-24 |
| `uncl2005.php` | `UNCL2005` | invoice period description code | BR-CL-23 |
| `uncl5189.php` | `UNCL5189` | allowance reason codes | BR-CL-20 |
| `uncl7161.php` | `UNCL7161` | charge reason codes | BR-CL-21 |
| `PEPPOL-EN16931-UBL.sch` | _(not an enum case)_ | Schematron rules file | all EN16931 rules |

## Updating a file

1. Download the latest values from the upstream Peppol BIS Billing 3.0 specification.
2. Edit the matching `.php` file — the array must remain a flat `string[]`.
3. No class changes required; `CodeList::clearCache()` exists for tests that need
   a clean slate after an in-process update.

## Quarterly update reminder

OpenPEPPOL publishes code-list updates alongside each Peppol BIS Billing release.
After any OpenPEPPOL release, compare each file's values against the specification
pages linked in the docblock at the top of that file.

## Overlap with DownloadedXml/

`eaid.php` (flat scheme-ID list for validation) and `DownloadedXml/eas.xml`
(VEFA XML with Id + Name for the EAS dropdown) both cover Electronic Address
Scheme codes.  Similarly `uncl7161.php` and `DownloadedXml/UNCL7161.xml` both
cover charge reason codes.  They must be kept in sync — if the upstream code list
adds or removes a code, **both** the `.php` validation file and the `.xml` dropdown
file need refreshing.
