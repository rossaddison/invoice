# PeppolValidator Documentation

## Overview

`PeppolValidator` is a comprehensive PHP class for validating PEPPOL BIS Billing 3.0 compliant XML documents (invoices and credit notes). It implements validation rules from the official PEPPOL specification and provides detailed error reporting with line number tracking.

**Version:** 1.1.0  
**License:** MIT  
**Namespace:** `App\Invoice\Helpers\Peppol`

## Features

- ✅ Complete PEPPOL BIS Billing 3.0 validation
- ✅ Line number tracking for errors and warnings
- ✅ XPath-based element location tracking
- ✅ Support for both Invoice and CreditNote documents
- ✅ Comprehensive error and warning reporting
- ✅ Multi-language support via TranslatorInterface
- ✅ ISO code list validation (countries, currencies, MIME types, etc.)

## Installation

```php
use App\Invoice\Helpers\Peppol\PeppolValidator;
use Yiisoft\Translator\TranslatorInterface;

$validator = new PeppolValidator($translator);
```

## Basic Usage

```php
// Load XML content
$xmlContent = file_get_contents('invoice.xml');
$validator->loadXML($xmlContent);

// Validate the document
$isValid = $validator->validate();

// Get validation results
if ($isValid) {
    echo "Document is valid!";
} else {
    $errors = $validator->getFormattedErrors();
    foreach ($errors as $error) {
        echo $error . "\n";
    }
}

// Get detailed summary
$summary = $validator->getSummary();
print_r($summary);
```

## Class Structure

### Constructor

```php
public function __construct(TranslatorInterface $t)
```

Initializes the validator with a translator instance and sets up code lists (ISO 3166, ISO 4217, UNCL codes, etc.).

### Main Methods

#### `loadXML(string $xmlContent): bool`

Loads and parses XML content for validation.

**Parameters:**
- `$xmlContent` - The XML string to validate

**Returns:** `true` on successful load, `false` on XML parsing errors

**Example:**
```php
$success = $validator->loadXML($xmlContent);
if (!$success) {
    $errors = $validator->getErrors();
}
```

#### `validate(): bool`

Runs all validation rules against the loaded document.

**Returns:** `true` if document is valid (no errors), `false` otherwise

**Validation Categories:**
- Empty elements (PEPPOL-EN16931-R008)
- Document level rules
- Party information
- Allowances and charges
- Payment means
- Currency consistency

#### `isValid(): bool`

Checks if the document passed validation.

**Returns:** `true` if no errors exist

#### `getErrors(): array`

Returns raw error array with detailed information.

**Returns:** Array of error objects containing:
```php
[
    'rule' => string,      // Rule identifier (e.g., "PEPPOL-EN16931-R001")
    'text' => string,      // Error message
    'line' => int|null,    // Line number in XML
    'xpath' => string|null // XPath to the element
]
```

#### `getWarnings(): array`

Returns raw warning array with similar structure to errors.

#### `getFormattedErrors(): array`

Returns user-friendly formatted error messages.

**Returns:** Array of strings like:
```
[Line 42] PEPPOL-EN16931-R001: Business process MUST be provided (at /Invoice[1]/cbc:ProfileID[1])
```

#### `getFormattedWarnings(): array`

Returns user-friendly formatted warning messages.

#### `getSummary(): array`

Returns comprehensive validation summary.

**Returns:**
```php
[
    'valid' => bool,
    'error_count' => int,
    'warning_count' => int,
    'errors' => array,
    'warnings' => array,
    'document_type' => string|null,      // 'Invoice' or 'CreditNote'
    'profile' => string|null,            // e.g., '01' for basic invoice
    'supplier_country' => string|null,   // ISO 3166 code
    'customer_country' => string|null    // ISO 3166 code
]
```

## Validation Rules Implemented

### Document Level Rules

| Rule ID | Description |
|---------|-------------|
| PEPPOL-EN16931-R001 | Business process MUST be provided |
| PEPPOL-EN16931-R002 | Max one note allowed (unless both parties are DE) |
| PEPPOL-EN16931-R003 | Buyer reference or order reference required |
| PEPPOL-EN16931-R004 | Specification identifier required and valid format |
| PEPPOL-EN16931-R005 | Tax currency must differ from document currency |
| PEPPOL-EN16931-R007 | Business process format validation |
| PEPPOL-EN16931-R008 | Empty elements not allowed |

### Tax Rules

| Rule ID | Description |
|---------|-------------|
| PEPPOL-EN16931-R053 | One tax total with subtotals required |
| PEPPOL-EN16931-R054 | Tax total without subtotals validation |
| PEPPOL-EN16931-R055 | Tax amounts must have same sign |

### Allowance/Charge Rules

| Rule ID | Description |
|---------|-------------|
| PEPPOL-EN16931-R040 | Amount must equal base × percentage/100 |
| PEPPOL-EN16931-R041 | Base amount required when percentage provided |
| PEPPOL-EN16931-R042 | Percentage required when base amount provided |
| PEPPOL-EN16931-R043 | Charge indicator must be true or false |
| PEPPOL-EN16931-R044 | Price level charge not allowed |
| PEPPOL-EN16931-R046 | Item net price calculation validation |

### Party Rules

| Rule ID | Description |
|---------|-------------|
| PEPPOL-EN16931-R010 | Buyer electronic address required |
| PEPPOL-EN16931-R020 | Seller electronic address required |

### Payment Rules

| Rule ID | Description |
|---------|-------------|
| PEPPOL-EN16931-R061 | Mandate reference required for direct debit |

### Currency Rules

| Rule ID | Description |
|---------|-------------|
| PEPPOL-EN16931-R051 | All amounts must use document currency |

### Credit Note Rules

| Rule ID | Description |
|---------|-------------|
| PEPPOL-EN16931-R080 | Max one project reference allowed |

## Code Lists

The validator includes the following code lists for validation:

- **ISO 3166:** Country codes (all standard codes including 1A, XI)
- **ISO 4217:** Currency codes (all standard currency codes)
- **MIME Codes:** Supported attachment types
  - application/pdf
  - image/png
  - image/jpeg
  - text/csv
  - application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
  - application/vnd.oasis.opendocument.spreadsheet
- **UNCL 2005:** Date/time format codes (3, 35, 432)
- **UNCL 5189:** Allowance/charge reason codes
- **UNCL 7161:** Special service codes
- **EAID:** Electronic address scheme codes

## Advanced Usage

### Custom Error Handling

```php
$validator->loadXML($xmlContent);
$validator->validate();

$summary = $validator->getSummary();

foreach ($summary['errors'] as $error) {
    $ruleId = $error['rule'];
    $message = $error['text'];
    $line = $error['line'] ?? 'unknown';
    $xpath = $error['xpath'] ?? 'N/A';
    
    // Custom logging or processing
    error_log("Validation Error [$ruleId] at line $line: $message");
}
```

### Checking Specific Rules

```php
$validator->loadXML($xmlContent);
$validator->validate();

$errors = $validator->getErrors();
$r001Errors = array_filter($errors, function($error) {
    return str_starts_with($error['rule'], 'PEPPOL-EN16931-R001');
});

if (!empty($r001Errors)) {
    echo "Missing business process ID";
}
```

### Integration with Web Applications

```php
class InvoiceController
{
    public function validateAction(Request $request)
    {
        $xmlContent = $request->getContent();
        
        $validator = new PeppolValidator($this->translator);
        
        if (!$validator->loadXML($xmlContent)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid XML format'
            ], 400);
        }
        
        $isValid = $validator->validate();
        $summary = $validator->getSummary();
        
        return new JsonResponse([
            'success' => $isValid,
            'summary' => $summary,
            'formatted_errors' => $validator->getFormattedErrors()
        ]);
    }
}
```

## Private Methods Overview

### Document Extraction

- `extractDocumentVariables()` - Extracts document type, profile, countries, and currency
- `extractSupplierCountry()` - Determines supplier country from VAT or address
- `extractCustomerCountry()` - Determines customer country from VAT or address

### Validation Methods

- `validateEmptyElements()` - Checks for empty XML elements
- `validateDocumentLevel()` - Validates document-level requirements
- `validateNoteRestrictions()` - Validates note count based on country rules
- `validateBuyerReference()` - Ensures buyer reference or order reference exists
- `validateCustomizationID()` - Validates specification identifier format
- `validateTaxTotals()` - Validates tax total structure and amounts
- `validateTaxAmountsSameSign()` - Ensures tax amounts have consistent signs
- `validateParties()` - Validates party information
- `validateAllowanceCharge()` - Validates all allowances and charges
- `validateSingleAllowanceCharge()` - Validates individual allowance/charge
- `validateAllowanceChargeCalculation()` - Validates amount calculations
- `validatePriceLevelAllowances()` - Validates price-level allowances
- `validatePriceCalculation()` - Validates price calculations
- `validatePaymentMeans()` - Validates payment method requirements
- `validateCurrency()` - Validates currency consistency
- `validateAmountCurrency()` - Validates individual amount currencies

### Utility Methods

- `getNodeValue()` - Retrieves text value from XPath query
- `getNode()` - Retrieves DOM node from XPath query
- `getNodeXPath()` - Builds XPath expression for a given node
- `getXMLErrors()` - Formats libxml parsing errors
- `addError()` - Adds error with line tracking
- `addWarning()` - Adds warning with line tracking

## XML Namespaces

The validator registers and uses the following namespaces:

```php
'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'
'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2'
'ubl-invoice' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2'
'ubl-creditnote' => 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2'
```

## Error Object Structure

Each error/warning contains:

```php
[
    'rule' => 'PEPPOL-EN16931-RXXX',  // Rule identifier (first 19 chars)
    'text' => 'Description of error',  // Error message
    'line' => 42,                      // Line number (or null)
    'xpath' => '/Invoice[1]/...'      // XPath location (or null)
]
```

## Dependencies

- PHP 8.0+
- DOM extension
- libxml extension
- Yiisoft\Translator\TranslatorInterface

## Translation Keys

The validator uses translation keys for error messages. Example keys:

- `peppol.unknown.document.type`
- `PEPPOL.EN16931.R001`
- `PEPPOL.EN16931.R002`
- `PEPPOL.EN16931.R003`
- etc.

Ensure your translator has these keys defined for proper internationalization.

## Performance Considerations

- The validator loads the entire XML document into memory
- XPath queries are used extensively for element lookup
- For large batches, consider reusing the validator instance:

```php
$validator = new PeppolValidator($translator);

foreach ($xmlFiles as $file) {
    $validator->loadXML(file_get_contents($file));
    $validator->validate();
    // Process results
}
```

## Limitations

- Only validates PEPPOL BIS Billing 3.0 format
- Does not validate against XSD schemas (focuses on business rules)
- Does not validate mathematical totals (line item sums, tax calculations, etc.)
- Limited to Invoice and CreditNote document types

## Best Practices

1. **Always check loadXML() return value** before calling validate()
2. **Use getSummary()** for comprehensive validation results
3. **Implement proper error logging** for production environments
4. **Cache validator instances** when validating multiple documents
5. **Provide translation keys** for all supported languages
6. **Handle both errors and warnings** in your application logic

## Example: Complete Validation Workflow

```php
use App\Invoice\Helpers\Peppol\PeppolValidator;

function validatePeppolInvoice(string $xmlPath, TranslatorInterface $translator): array
{
    $validator = new PeppolValidator($translator);
    
    // Step 1: Load XML
    $xmlContent = file_get_contents($xmlPath);
    if (!$validator->loadXML($xmlContent)) {
        return [
            'success' => false,
            'stage' => 'load',
            'errors' => $validator->getFormattedErrors()
        ];
    }
    
    // Step 2: Validate
    $isValid = $validator->validate();
    
    // Step 3: Get results
    $summary = $validator->getSummary();
    
    return [
        'success' => $isValid,
        'stage' => 'validation',
        'summary' => $summary,
        'formatted_errors' => $validator->getFormattedErrors(),
        'formatted_warnings' => $validator->getFormattedWarnings()
    ];
}

// Usage
$result = validatePeppolInvoice('invoice.xml', $translator);

if ($result['success']) {
    echo "✓ Invoice is PEPPOL compliant\n";
} else {
    echo "✗ Validation failed:\n";
    foreach ($result['formatted_errors'] as $error) {
        echo "  - $error\n";
    }
}
```

## Troubleshooting

### "Invalid XML" Error
- Ensure XML is well-formed
- Check for encoding issues (UTF-8 recommended)
- Validate against UBL XSD schemas first

### Missing Translation Keys
- Implement fallback translations
- Check translator configuration
- Verify all PEPPOL rule keys are defined

### Line Numbers Not Appearing
- Ensure `preserveWhiteSpace = false` in DOMDocument
- Check that original XML has proper line breaks
- Some XML minifiers may remove line number information

## References

- [PEPPOL BIS Billing 3.0 Documentation](https://docs.peppol.eu/poacc/billing/3.0/)
- [PEPPOL Validation Rules](https://docs.peppol.eu/poacc/billing/3.0/2025-Q4/rules/ubl-peppol/)
- [UBL 2.1 Specification](http://docs.oasis-open.org/ubl/UBL-2.1.html)

## License

MIT License - See project license file for details.

## Version History

- **1.1.0** - Added complete line number tracking and XPath support
- **1.0.0** - Initial release with core PEPPOL validation rules
