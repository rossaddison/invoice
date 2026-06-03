# Peppol Schematron Validator — Route 1 (DOM-based runtime evaluation)

Replaces the hand-written `PeppolValidator` rule methods with direct runtime
evaluation of the official `PEPPOL-EN16931-UBL.sch` file via
`SchematronRuleRunner` + `ExpressionEvaluator`.

---

## Overview

```
PEPPOL-EN16931-UBL.sch
        │
        ▼
  SchematronParser          parses .sch XML into Rule / Assertion / Expression AST
        │
        ▼
  SchematronRuleRunner      evaluates every assertion against the invoice DOMDocument
        │
        ▼
  ExpressionEvaluator       executes XPath 2.0 expression AST in PHP
        │
        ▼
  ValidationViolation[]     fed back into PeppolValidator errors / warnings
```

When `resources/peppol/PEPPOL-EN16931-UBL.sch` is present the runner fires
automatically; the hand-written rule methods are skipped to avoid duplicates.
When the file is absent the three hand-written EN16931 classes (R001–R003)
remain as a fallback.

---

## Activation

Download the Schematron file from the Peppol authority and place it at:

```
resources/peppol/PEPPOL-EN16931-UBL.sch
```

No code change is required — `PeppolValidator::validate()` detects the file and
switches paths automatically.

---

## XPath 2.0 functions implemented

| Function | AST node | Notes |
|----------|----------|-------|
| `normalize-space()` | `NormalizeSpace` | 0-arg defaults to context node |
| `string-length()` | `StringLength` | 0-arg defaults to context node; `mb_strlen` |
| `string()` | `StringCast` | 0-arg defaults to context node |
| `number()` | `Decimal` | alias for `xs:decimal` |
| `substring(s, start[, len])` | `Substring` | 1-based start → 0-based `mb_substr` |
| `translate(s, from, to)` | `Translate` | XPath delete-when-beyond-to semantics |
| `castable as xs:date` | `CastableAs` | `checkdate()` with `Y-m-d` pattern |
| `castable as xs:integer` | `CastableAs` | `ctype_digit` after sign strip |
| `castable as xs:decimal` | `CastableAs` | `is_numeric` |
| `castable as xs:boolean` | `CastableAs` | `true/false/1/0` |
| `castable as xs:string` | `CastableAs` | always true |
| `(A, B, ...)` sequence | `Sequence` | truthy when any item yields nodes |
| `for $v in seq return expr` | `ForExpression` | maps over `evalSequence` result |
| `preceding-sibling::` etc. | `T_DCOLON` token | axis `::` tokenised, passed to DOMXPath |

All other XPath 2.0 functions (e.g. `tokenize`, `string-join`,
`string-to-codepoints`) fall through to `FunctionCall` and are silently skipped
per assertion — the `SchematronRuleRunner` wraps each assertion evaluation in a
`try-catch` for `RuntimeException`.

---

## Checksum functions

The ten Peppol `u:` checksum functions are wired from `PeppolValidator`'s
existing private `check*` methods via `checksumHandlers()`:

| Schematron function | `ChecksumAlgorithm` | PHP method |
|---------------------|---------------------|------------|
| `u:gln` | `GLN` | `checkGLN` |
| `u:mod11` | `Mod11` | `checkMod11` |
| `u:mod97-0208` | `Mod97BE` | `checkMod97BE` |
| `u:checkSEOrgnr` | `SEOrgnr` | `checkSEOrgnr` |
| `u:abn` | `ABN` | `checkABN` |
| `u:checkCF` | `CodiceFiscale` | `checkCF` |
| `u:checkPIVAseIT` | `PIVAseIT` | `checkPIVAseIT` |
| `u:checkCodiceIPA` | `CodiceIPA` | `checkCodiceIPA` |
| `u:checkDanishCVR` | `DanishCVR` | `checkDanishCVR` |

`u:TinVerification` and `u:slack` have no registered handler and are skipped
silently.

---

## Schematron file path

Resolved via Yii3 `Aliases` in `PeppolValidator::schPath()`:

```php
private static function schPath(): string
{
    $aliases = new Aliases(['@peppol' => dirname(__DIR__, 4) . '/resources/peppol']);
    return $aliases->get('@peppol') . '/PEPPOL-EN16931-UBL.sch';
}
```

---

## Rule fallback when .sch absent

```
is_file(schPath())  true  → runSchematron()       SchematronRuleRunner
                    false → runHandwrittenRules()  PEPPOL_EN16931_R001–R003
```

The `validateWithCalculators()` path (`MonetaryTotalCalculator`,
`TaxCalculator`, `InvoiceLineCalculator`) always runs regardless of which rule
path is active.

---

## Known gaps (silently skipped assertions)

| Rule | Reason |
|------|--------|
| `GR-R-*` (Greek TIN) | Requires `tokenize()` + sequence indexing `$var[n]` |
| `DE-R-019` (German IBAN) | Requires `string-to-codepoints()`, `string-join()`, `replace()` |
| `u:TinVerification` | No registered checksum handler |

---

## UBL generation fixes applied alongside validator work

| File | Fix |
|------|-----|
| `src/Invoice/Ubl/Contact.php` | `cbc:Telephone` guard extended to exclude empty strings |
| `src/Invoice/Helpers/Peppol/PeppolHelper.php` | `cbc:Note`, `cbc:Description`, `cac:OriginCountry` conditionally omitted when source value is blank |
| `src/Invoice/Ubl/TaxTotal.php` | Document-currency `TaxAmount` (BT-110) now written first in two-currency case; malformed `@var` docblocks replaced with explicit casts |

---

## Tests

```
Tests/Unit/Invoice/Peppol/
  XPathTokenizerTest.php       tokeniser including T_DCOLON axis separator
  XPathParserTest.php          all new AST nodes and grammar rules
  ExpressionEvaluatorTest.php  NormalizeSpace, StringLength, Substring, Translate,
                                CastableAs, Sequence, ForExpression, evalBinary fixes
  SchematronParserTest.php     parse → SchematronDocument round-trip
  SchematronRuleRunnerTest.php runtime evaluation against inline .sch XML
  EN16931RulesTest.php         hand-written R001–R003 including R001 empty-element fix
```

Run with:

```bash
vendor/bin/phpunit Tests/Unit/Invoice/Peppol/
```
