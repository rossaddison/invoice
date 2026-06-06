# Peppol Schematron Code Generation

Generates typed validator files for PHP, TypeScript, and Scala directly from the
official Peppol BIS Billing 3.0 Schematron `.sch` file.  When the Schematron
version bumps, re-run the relevant script — no manual rule editing required.

---

## Pipeline overview

```
PEPPOL-EN16931-UBL.sch
        │
        ▼
  SchematronParser          parses XML into Rule / Assertion / Expression AST
        │
        ▼
  SchematronDocument        { namespaces, variables, rules[] }
        │
        ├──► PhpRuleEmitter      ──► PeppolValidators.php   (PHP functions)
        ├──► TypeScriptRuleEmitter ──► validators.ts         (TS exports)
        └──► ScalaRuleEmitter    ──► PeppolValidators.scala  (Scala 3 defs)
```

Each emitter delegates XPath → target-language expression translation to a
paired `*ExpressionEmitter` + `*VoPathMapper`.

---

## Getting the Schematron file

The `.sch` file is **not bundled** in this repo.  Download the latest artefact
from the Peppol authority and place it at:

```
resources/peppol/PEPPOL-EN16931-UBL.sch
```

---

## Generating validators

### PHP

```bash
php bin/generate-php-validators.php
```

| Option | Default |
|--------|---------|
| `--sch=PATH` | `resources/peppol/PEPPOL-EN16931-UBL.sch` |
| `--out=PATH` | `src/Invoice/Helpers/Peppol/Generated/PeppolValidators.php` |
| `--ns=NAMESPACE` | `App\Invoice\Helpers\Peppol\Generated` |

Output: one free function per assertion, e.g.:

```php
function validatePEPPOLEN16931R001(UblInvoiceVO $v): array
{
    return trim((string)($v->profileId)) != ''
        ? []
        : [new Violation('PEPPOL-EN16931-R001', Severity::Fatal, '...')];
}
```

### TypeScript

```bash
php bin/generate-ts-validators.php
```

| Option | Default |
|--------|---------|
| `--sch=PATH` | `resources/peppol/PEPPOL-EN16931-UBL.sch` |
| `--out=PATH` | `angular/src/app/peppol/validators.ts` |
| `--vo=IMPORT_PREFIX` | `../vo` |

Output: one exported function per assertion, e.g.:

```typescript
export function validatePEPPOLEN16931R001(v: UblInvoiceVO): Violation[] {
  return (v.profileId != null && v.profileId !== '')
    ? []
    : [{ id: 'PEPPOL-EN16931-R001', severity: Severity.Fatal, message: '...' }];
}
```

### Scala

```bash
php bin/generate-scala-validators.php
```

| Option | Default |
|--------|---------|
| `--sch=PATH` | `resources/peppol/PEPPOL-EN16931-UBL.sch` |
| `--out=PATH` | `src/scala/peppol/rules/PeppolValidators.scala` |
| `--pkg=PACKAGE` | `peppol.rules` |
| `--vo=VO_PACKAGE` | `peppol.vo` |

Output: one top-level `def` per assertion, e.g.:

```scala
def validatePEPPOLEN16931R001(v: UblInvoiceVO): Seq[Violation] =
  if (v.profileId.trim.nonEmpty)
    Seq.empty
  else
    Seq(Violation("PEPPOL-EN16931-R001", Severity.Fatal, "..."))
```

---

## Value Object (VO) layer

All generated code operates against a typed VO tree — no DOM, no XPath at
validation time.  The VOs live in `src/Invoice/Helpers/Peppol/Emit/Vo/`:

| Class | Covers |
|-------|--------|
| `UblInvoiceVO` | Root invoice / credit note |
| `UblInvoiceLineVO` | `cac:InvoiceLine` and `cac:CreditNoteLine` |
| `UblTaxTotalVO` | `cac:TaxTotal` |
| `UblTaxSubtotalVO` | `cac:TaxSubtotal` |
| `UblTaxCategoryVO` | `cac:ClassifiedTaxCategory` |
| `UblAllowanceChargeVO` | `cac:AllowanceCharge` |
| `UblPaymentMeansVO` | `cac:PaymentMeans` |
| `UblPartyVO` | Supplier, customer, tax representative |
| `UblLegalMonetaryTotalVO` | `cac:LegalMonetaryTotal` |
| `UblOrderReferenceVO` | `cac:OrderReference` |
| `Violation` | Rule id + severity + message |

---

## PHP upgrade path

The current `PeppolValidator` runs XPath queries against a live `DOMDocument`
for every rule.  The generated PHP replaces that with:

1. **Hydrator** (hand-written, one-time) — reads the `DOMDocument` and builds a
   `UblInvoiceVO` tree once at load time.
   Suggested path: `src/Invoice/Helpers/Peppol/UblInvoiceHydrator.php`

2. **Generated validators** — produced by `bin/generate-php-validators.php`;
   pure PHP functions that receive the VO, no DOM dependency.

3. **Runner** — replaces `PeppolValidator::validateWithRegistry()`:

```php
$vo         = $hydrator->hydrate($this->dom);
$violations = array_merge(
    \App\Invoice\Helpers\Peppol\Generated\validatePEPPOLEN16931R001($vo),
    \App\Invoice\Helpers\Peppol\Generated\validatePEPPOLEN16931R002($vo),
    // ...
);
```

Once the hydrator and runner are in place, the hand-coded classes under
`src/Invoice/Helpers/Peppol/Rule/EN16931/` (currently R001–R003) and
`RuleRegistry` can be deleted — the full rule set is covered by the generated
file.

---

## Source classes

| Class | Path |
|-------|------|
| `SchematronParser` | `src/Invoice/Helpers/Peppol/SchematronParser.php` |
| `SchematronDocument` | `src/Invoice/Helpers/Peppol/SchematronDocument.php` |
| `PhpRuleEmitter` | `src/Invoice/Helpers/Peppol/Emit/PhpRuleEmitter.php` |
| `PhpExpressionEmitter` | `src/Invoice/Helpers/Peppol/Emit/PhpExpressionEmitter.php` |
| `VoPathMapper` | `src/Invoice/Helpers/Peppol/Emit/VoPathMapper.php` |
| `TypeScriptRuleEmitter` | `src/Invoice/Helpers/Peppol/Emit/TypeScriptRuleEmitter.php` |
| `TypeScriptExpressionEmitter` | `src/Invoice/Helpers/Peppol/Emit/TypeScriptExpressionEmitter.php` |
| `TypeScriptVoPathMapper` | `src/Invoice/Helpers/Peppol/Emit/TypeScriptVoPathMapper.php` |
| `ScalaRuleEmitter` | `src/Invoice/Helpers/Peppol/Emit/ScalaRuleEmitter.php` |
| `ScalaExpressionEmitter` | `src/Invoice/Helpers/Peppol/Emit/ScalaExpressionEmitter.php` |
| `ScalaVoPathMapper` | `src/Invoice/Helpers/Peppol/Emit/ScalaVoPathMapper.php` |
| `XPathParser` | `src/Invoice/Helpers/Peppol/XPathParser.php` |
