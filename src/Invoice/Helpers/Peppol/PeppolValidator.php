<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Calculator\InvoiceLineCalculator;
use App\Invoice\Helpers\Peppol\Calculator\MonetaryTotalCalculator;
use App\Invoice\Helpers\Peppol\Calculator\TaxCalculator;
use App\Invoice\Helpers\Peppol\CodeList;
use App\Invoice\Helpers\Peppol\CodeLists;
use App\Invoice\Helpers\Peppol\Rule\EN16931\PEPPOL_EN16931_R001;
use App\Invoice\Helpers\Peppol\Rule\EN16931\PEPPOL_EN16931_R002;
use App\Invoice\Helpers\Peppol\Rule\EN16931\PEPPOL_EN16931_R003;
use App\Invoice\Helpers\Peppol\Rule\RuleRegistry;
use App\Invoice\Helpers\Peppol\Rule\Severity;
use App\Invoice\Helpers\Peppol\Rule\ValidationContext;
use App\Invoice\Helpers\Peppol\XPathHelper;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Complete PEPPOL BIS Billing 3.0 Validator with Line Number Tracking
 * using peppol rules at:
 *  https://docs.peppol.eu/poacc/billing/3.0/2025-Q4/rules/ubl-peppol/
 *
 * @version 1.1.0
 * @license MIT
 */
class PeppolValidator
{
    private DOMDocument $dom;
    private ?DOMXPath $xpath = null;

    /** @var array<int, array{rule: string, text: string, line: string|null, xpath: string|null}> */
    private array $errors = [];

    /** @var array<int, array{message: string, line: int|null, xpath: string|null}> */
    private array $warnings = [];

    private const string XPATH_PROFILE_ID        = '//cbc:ProfileID';
    private const string XPATH_CUSTOMIZATION_ID  = '//cbc:CustomizationID';
    private const string XPATH_SUPPLIER_PARTY    = '//cac:AccountingSupplierParty/cac:Party/';
    private const string XPATH_CUSTOMER_PARTY    = '//cac:AccountingCustomerParty/cac:Party/';
    private const string XPATH_TAX_SCHEME_VAT    = "cac:PartyTaxScheme[cac:TaxScheme/cbc:ID='VAT']/";
    private const string XPATH_TAX_CURRENCY_CODE = '//cbc:TaxCurrencyCode';
    private const string REGEX_10_DIGITS = '/^\d{10}$/';
    private const string REGEX_11_DIGITS = '/^\d{11}$/';

/** @var array<string, string> */
    private array $namespaces = [
        'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:'
            . 'CommonBasicComponents-2',
        'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:'
            . 'CommonAggregateComponents-2',
        'ubl-invoice' => 'urn:oasis:names:specification:ubl:schema:'
            . 'xsd:Invoice-2',
        'ubl-creditnote' => 'urn:oasis:names:specification:ubl:schema:'
            . 'xsd:CreditNote-2',
    ];

    /**
     * What?  The Peppol business-process profile number extracted from cbc:ProfileID,
     *        e.g. '01' for BIS Billing 3.0 ('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0').
     * Why?   The profile identifies which Peppol process governs this invoice; it must match
     *        the expected pattern or rules R001 and R007 will fire.
     * When?  Set once during extractDocumentVariables() when the XML is loaded; then read
     *        inside validateDocumentLevel().
     * Where? Checked in validateDocumentLevel() to detect an unrecognised profile format.
     * How?   Set by matching cbc:ProfileID against a regex; defaults to the translated
     *        'reason.unknown' string if the element is absent or the pattern does not match.
     */
    private ?string $profile = null;
    /**
     * What?  The two-letter ISO 3166 country code of the invoice seller, e.g. 'GB' or 'DE'.
     * Why?   Several Peppol rules are country-specific (e.g. rule R002 relaxes the note limit
     *        only for DE-to-DE invoices); the validator must know the supplier's country.
     * When?  Set once by extractSupplierCountry() during extractDocumentVariables() when the
     *        XML is loaded.
     * Where? Read in validateNoteRestrictions() alongside $customerCountry.
     * How?   The first two characters of the seller's VAT CompanyID are taken and uppercased;
     *        falls back to the tax representative VAT, then the postal address; defaults to 'XX'.
     */
    private ?string $supplierCountry = null;
    /**
     * What?  The two-letter ISO 3166 country code of the invoice buyer, e.g. 'GB' or 'DE'.
     * Why?   Needed alongside $supplierCountry to apply country-pair rules, currently the
     *        DE-to-DE note-count relaxation in rule R002.
     * When?  Set once by extractCustomerCountry() during extractDocumentVariables() when the
     *        XML is loaded.
     * Where? Read in validateNoteRestrictions() alongside $supplierCountry.
     * How?   The first two characters of the buyer's VAT CompanyID are taken and uppercased;
     *        falls back to the postal address country; defaults to 'XX' if neither is present.
     */
    private ?string $customerCountry = null;
    /**
     * What?  The three-letter ISO 4217 currency code declared in cbc:DocumentCurrencyCode,
     *        e.g. 'GBP' or 'EUR', that every monetary amount in the invoice must use.
     * Why?   Peppol rule R051 requires all amount elements to carry the document currency;
     *        rules BR-CL-04 and R005 also reference this value.
     * When?  Set once during extractDocumentVariables() when the XML is loaded.
     * Where? Read in validateCurrency(), validateCurrencyCodeLists(), validateTaxAmountsSameSign(),
     *        and validateTaxCurrencyNotEqualDocCurrency().
     * How?   Retrieved with getNodeValue('//cbc:DocumentCurrencyCode'); stored as a trimmed
     *        string or null if the element is absent.
     */
    private ?string $documentCurrencyCode = null;
    /**
     * What?  A string recording whether the loaded XML is an 'Invoice' or a 'CreditNote',
     *        detected from the document's root namespace.
     * Why?   Some Peppol rules only apply to one document type (e.g. R080 — max one project
     *        reference — is credit-note-only); the validator must branch on type.
     * When?  Set once during extractDocumentVariables() when the XML is loaded; checked at
     *        the top of validate() to abort early if still null.
     * Where? Read in validate() (null guard) and validateCreditNoteProjectReference().
     * How?   Set by querying for a //ubl-invoice:Invoice or //ubl-creditnote:CreditNote
     *        namespace-qualified root element; remains null if neither is found.
     */
    private ?string $documentType = null;

    public function __construct(
        private readonly TranslatorInterface $t
    )
    {
        $this->dom = new DOMDocument();
        $this->dom->preserveWhiteSpace = false;
    }

    /**
     * Load XML content
     *
     * @param string $xmlContent XML to validate
     * @return bool Success status
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function loadXML(string $xmlContent): bool
    {
        libxml_use_internal_errors(true);

        if (!$this->dom->loadXML($xmlContent)) {
            $this->addError('Invalid XML: ' . $this->getXMLErrors());
            return false;
        }

        $this->xpath = new DOMXPath($this->dom);

        foreach ($this->namespaces as $prefix => $uri) {
            $this->xpath->registerNamespace($prefix, $uri);
        }

        $this->extractDocumentVariables();

        return true;
    }

    private function extractDocumentVariables(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $invoices = $this->xpath->query('//ubl-invoice:Invoice');
        $creditNotes = $this->xpath->query(
            '//ubl-creditnote:CreditNote'
        );

        if ($invoices !== false && $invoices->length > 0) {
            $this->documentType = 'Invoice';
        } elseif ($creditNotes !== false && $creditNotes->length > 0) {
            $this->documentType = 'CreditNote';
        }

        $profileID = $this->getNodeValue(self::XPATH_PROFILE_ID);
        if ($profileID !== null) {
            $pattern = '/urn:fdc:peppol\.eu:2017:poacc:billing:'
                . '(\d{2}):1\.0/';
            if (preg_match($pattern, $profileID, $matches) === 1) {
                $this->profile = $matches[1];
            } else {
                $this->profile = $this->t->translate('reason.unknown');
            }
        } else {
            $this->profile = $this->t->translate('reason.unknown');
        }

        $this->supplierCountry = $this->extractSupplierCountry();
        $this->customerCountry = $this->extractCustomerCountry();
        $this->documentCurrencyCode = $this->getNodeValue(
            '//cbc:DocumentCurrencyCode'
        );
    }

    private function extractSupplierCountry(): string
    {
        $vatPath = self::XPATH_SUPPLIER_PARTY
            . self::XPATH_TAX_SCHEME_VAT
            . 'cbc:CompanyID';
        $vatCountry = $this->getNodeValue($vatPath);

        if ($vatCountry !== null && strlen($vatCountry) >= 2) {
            return strtoupper(substr($vatCountry, 0, 2));
        }

        $taxRepPath = '//cac:TaxRepresentativeParty/'
            . self::XPATH_TAX_SCHEME_VAT
            . 'cbc:CompanyID';
        $taxRepCountry = $this->getNodeValue($taxRepPath);

        if ($taxRepCountry !== null && strlen($taxRepCountry) >= 2) {
            return strtoupper(substr($taxRepCountry, 0, 2));
        }

        $addressPath = self::XPATH_SUPPLIER_PARTY
            . 'cac:PostalAddress/cac:Country/cbc:IdentificationCode';
        $country = $this->getNodeValue($addressPath);

        return $country !== null ? strtoupper($country) : 'XX';
    }

    private function extractCustomerCountry(): string
    {
        $vatPath = self::XPATH_CUSTOMER_PARTY
            . self::XPATH_TAX_SCHEME_VAT
            . 'cbc:CompanyID';
        $custVatCountry = $this->getNodeValue($vatPath);

        if ($custVatCountry !== null && strlen($custVatCountry) >= 2) {
            return strtoupper(substr($custVatCountry, 0, 2));
        }

        $addressPath = self::XPATH_CUSTOMER_PARTY
            . 'cac:PostalAddress/cac:Country/cbc:IdentificationCode';
        $country = $this->getNodeValue($addressPath);

        return $country !== null ? strtoupper($country) : 'XX';
    }

    /**
     * Run all validations
     *
     * @return bool True if valid
     */
    public function validate(): bool
    {
        $this->errors = [];
        $this->warnings = [];

        if ($this->documentType === null) {
            $this->addError($this->t->translate('peppol.unknown.document.type'));
            return false;
        }

        $this->validateUBLVersion();
        $this->validateEmptyElements();
        $this->validateDocumentLevel();
        $this->validateParties();
        $this->validateAllowanceCharge();
        $this->validatePaymentMeans();
        $this->validateCurrency();
        $this->validateCodeLists();
        $this->validateWithCalculators();
        $this->validateWithRegistry();

        return empty($this->errors);
    }

    /**
     * Run all registered ValidationRule classes and fan their violations into
     * $this->errors (Fatal) or $this->warnings (Warning / Info), preserving the
     * existing array shape expected by getErrors() and getWarnings().
     */
    private function validateWithRegistry(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $context = new ValidationContext(
            documentType:         $this->documentType,
            documentCurrencyCode: $this->documentCurrencyCode,
            supplierCountry:      $this->supplierCountry,
            customerCountry:      $this->customerCountry,
            profile:              $this->profile,
        );

        $registry = new RuleRegistry();
        $registry->register(
            new PEPPOL_EN16931_R001($this->t),
            new PEPPOL_EN16931_R002($this->t),
            new PEPPOL_EN16931_R003($this->t),
        );

        foreach ($registry->run($this->xpath, $context) as $v) {
            if ($v->severity === Severity::Warning
                || $v->severity === Severity::Info
            ) {
                $this->warnings[] = [
                    'message' => $v->ruleId . ': ' . $v->message,
                    'line'    => $v->line !== null ? (int) $v->line : null,
                    'xpath'   => $v->xpath,
                ];
            } else {
                $this->errors[] = [
                    'rule'  => $v->ruleId,
                    'text'  => $v->message,
                    'line'  => $v->line,
                    'xpath' => $v->xpath,
                ];
            }
        }
    }

    /**
     * Delegate rule sets to dedicated calculator classes and merge their errors.
     */
    private function validateWithCalculators(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $calculators = [
            new MonetaryTotalCalculator($this->xpath, $this->t),
            new TaxCalculator($this->xpath, $this->t),
            new InvoiceLineCalculator($this->xpath, $this->t),
        ];

        foreach ($calculators as $calculator) {
            $calculator->validate();
            array_push($this->errors, ...$calculator->getErrors());
        }
    }

    private function validateUBLVersion(): void
    {
        $version = $this->getNodeValue('//cbc:UBLVersionID');

        if ($version !== null && $version !== '2.4') {
            $this->addError(
                'UBL-VERSION       : '
                . $this->t->translate('ubl.version.required.2.4')
                . ' (' . $version . ')',
                $this->getNode('//cbc:UBLVersionID')
            );
        }
    }

    /**
     * PEPPOL-EN16931-R008: Empty elements
     */
    private function validateEmptyElements(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $allElements = $this->xpath->query('//*');
        if ($allElements === false) {
            return;
        }

        foreach ($allElements as $element) {
            if (!($element instanceof DOMElement)) {
                continue;
            }

            $hasChildren = $element->hasChildNodes();
            $nodeValue = $element->nodeValue;
            $hasValue = $nodeValue !== null
                && trim($nodeValue) !== '';

            if (!$hasChildren && !$hasValue) {
                // Empty Element:
                $this->addError(
                    'PEPPOL-EN16931-R008: '
                    . $this->t->translate('PEPPOL.EN16931.R008')
                    . $element->nodeName,
                    $element
                );
            }
        }
    }

    private function validateDocumentLevel(): void
    {
        // R001 (ProfileID present) and R002/R003 are handled by the RuleRegistry.
        if ($this->profile === $this->t->translate('reason.unknown')) {
            $this->addError(
                'PEPPOL-EN16931-R007: '
                . $this->t->translate('PEPPOL.EN16931.R007')
                . "'urn:fdc:peppol.eu:2017:poacc:billing:NN:1.0'",
                $this->getNode(self::XPATH_PROFILE_ID)
            );
        }

        $this->validateCustomizationID();
        $this->validateTaxTotals();
    }

    private function validateCustomizationID(): void
    {
        $customizationID = $this->getNodeValue(
            self::XPATH_CUSTOMIZATION_ID
        );

        $requiredStart = 'urn:cen.eu:en16931:2017#compliant#'
            . 'urn:fdc:peppol.eu:2017:poacc:billing:3.0';

        if ($customizationID === null) {
            // Specification identifier required
            $this->addError(
                'PEPPOL-EN16931-R004: ' .
                $this->t->translate('PEPPOL.EN16931.R004.REQUIRED'),
                $this->getNode(self::XPATH_CUSTOMIZATION_ID)
            );
            return;
        }

        if (!str_starts_with($customizationID, $requiredStart)) {
            // Invalid specification identifier format
            $this->addError(
                'PEPPOL-EN16931-R004: '
                . $this->t->translate('PEPPOL.EN16931.R004.INVALID'),
                $this->getNode(self::XPATH_CUSTOMIZATION_ID)
            );
        }
    }

    private function validateTaxTotals(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $taxCurrencyCode = $this->getNodeValue(self::XPATH_TAX_CURRENCY_CODE);

        $this->validateTaxTotalWithSubtotal();
        $this->validateTaxTotalWithoutSubtotal($taxCurrencyCode);
        $this->validateTaxAmountsSameSign($taxCurrencyCode);
        $this->validateTaxCurrencyNotEqualDocCurrency($taxCurrencyCode);
        $this->validateCreditNoteProjectReference();
    }

    private function validateTaxTotalWithSubtotal(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $taxTotalWithSub = $this->xpath
            ->query('//cac:TaxTotal[cac:TaxSubtotal]');

        if ($taxTotalWithSub !== false
            && $taxTotalWithSub->length !== 1
        ) {
            $node = $taxTotalWithSub->length > 0
                ? $taxTotalWithSub->item(1)
                : $this->getNode('//cac:TaxTotal');
            $this->addError(
                'PEPPOL-EN16931-R053: '
                . $this->t->translate('PEPPOL.EN16931.R053'),
                ($node instanceof DOMNode) ? $node : null
            );
        }
    }

    private function validateTaxTotalWithoutSubtotal(
        ?string $taxCurrencyCode
    ): void {
        if ($this->xpath === null) {
            return;
        }

        $taxTotalWithoutSub = $this->xpath
            ->query('//cac:TaxTotal[not(cac:TaxSubtotal)]');

        if ($taxTotalWithoutSub === false) {
            return;
        }

        $expectedCount = $taxCurrencyCode !== null ? 1 : 0;

        if ($taxTotalWithoutSub->length !== $expectedCount) {
            $node = $taxTotalWithoutSub->length > 0
                ? $taxTotalWithoutSub->item(0)
                : null;
            $this->addError(
                'PEPPOL-EN16931-R054: '
                . $this->t->translate('PEPPOL.EN16931.R054'),
                ($node instanceof DOMNode) ? $node : null
            );
        }
    }

    private function validateTaxCurrencyNotEqualDocCurrency(
        ?string $taxCurrencyCode
    ): void {
        if ($taxCurrencyCode !== null
            && $this->documentCurrencyCode !== null
            && $taxCurrencyCode === $this->documentCurrencyCode
        ) {
            $this->addError(
                'PEPPOL-EN16931-R005: '
                . $this->t->translate('PEPPOL.EN16931.R005'),
                $this->getNode(self::XPATH_TAX_CURRENCY_CODE)
            );
        }
    }

    private function validateCreditNoteProjectReference(): void
    {
        if ($this->documentType !== 'CreditNote'
            || $this->xpath === null
        ) {
            return;
        }

        $projectRefs = $this->xpath->query(
            "//cac:AdditionalDocumentReference[cbc:DocumentTypeCode='50']"
        );

        if ($projectRefs !== false && $projectRefs->length > 1) {
            $node = $projectRefs->item(1);
            $this->addError(
                'PEPPOL-EN16931-R080: '
                . $this->t->translate('PEPPOL.EN16931.R080'),
                ($node instanceof DOMNode) ? $node : null
            );
        }
    }

    /**
     * @param string|null $taxCurrencyCode Tax currency
     */
    private function validateTaxAmountsSameSign(
        ?string $taxCurrencyCode
    ): void {
        if ($taxCurrencyCode === null
            || $this->documentCurrencyCode === null
            || $this->xpath === null
        ) {
            return;
        }

        $docCurrPath = "//cac:TaxTotal/cbc:TaxAmount"
            . "[@currencyID='{$this->documentCurrencyCode}']";
        $taxCurrPath = "//cac:TaxTotal/cbc:TaxAmount"
            . "[@currencyID='{$taxCurrencyCode}']";

        $docNodes = $this->xpath->query($docCurrPath);
        $taxNodes = $this->xpath->query($taxCurrPath);

        $docItem  = ($docNodes !== false) ? $docNodes->item(0) : null;
        $taxItem  = ($taxNodes !== false) ? $taxNodes->item(0) : null;

        $docValueStr  = $docItem?->nodeValue;
        $taxValueStr  = $taxItem?->nodeValue;
        $taxAmountDoc = $docValueStr !== null ? (float) $docValueStr : null;
        $taxAmountTax = $taxValueStr !== null ? (float) $taxValueStr : null;

        if ($taxAmountDoc === null || $taxAmountTax === null) {
            return;
        }

        $differentSigns = ($taxAmountDoc < 0 && $taxAmountTax > 0)
            || ($taxAmountDoc > 0 && $taxAmountTax < 0);

        if ($differentSigns) {
            $this->addError(
                'PEPPOL-EN16931-R055: '
                . $this->t->translate('PEPPOL.EN16931.R055'),
                ($docItem instanceof DOMNode) ? $docItem : null
            );
        }
    }

    private function validateParties(): void
    {
        $buyerEndpoint = $this->getNodeValue(
            self::XPATH_CUSTOMER_PARTY . 'cbc:EndpointID'
        );

        if ($buyerEndpoint === null) {
            // Buyer Electronic Address Required
            $this->addError($this->t->translate('PEPPOL.EN16931.R010'),
                $this->getNode(self::XPATH_CUSTOMER_PARTY . 'cbc:EndpointID')
            );
        }

        $sellerEndpoint = $this->getNodeValue(
            self::XPATH_SUPPLIER_PARTY . 'cbc:EndpointID'
        );

        if ($sellerEndpoint === null) {
            // Seller Electronic Address Required
            $this->addError(
                'PEPPOL-EN16931-R020: '
                . $this->t->translate('PEPPOL.EN16931.R020'),
                $this->getNode(self::XPATH_SUPPLIER_PARTY . 'cbc:EndpointID')
            );
        }
    }

    private function validateAllowanceCharge(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $allowanceCharges = $this->xpath
            ->query('//cac:AllowanceCharge');

        if ($allowanceCharges === false) {
            return;
        }

        foreach ($allowanceCharges as $ac) {
            if (!($ac instanceof DOMElement)) {
                continue;
            }
            $this->validateSingleAllowanceCharge($ac);
        }

        $this->validatePriceLevelAllowances();
    }

    /**
     * @param DOMElement $ac Allowance/charge element
     */
    private function validateSingleAllowanceCharge(
        DOMElement $ac
    ): void {
        $hasPercentage = $this->getNodeValue(
            'cbc:MultiplierFactorNumeric',
            $ac
        ) !== null;

        $hasBaseAmount = $this->getNodeValue(
            'cbc:BaseAmount',
            $ac
        ) !== null;

        $amountNode = $this->getNodeValue('cbc:Amount', $ac);
        $amount = $amountNode !== null ? (float)$amountNode : 0.0;

        $chargeIndicator = $this->getNodeValue(
            'cbc:ChargeIndicator',
            $ac
        );

        if ($hasPercentage && !$hasBaseAmount) {
            // Base amount required when percentage provided',
            $this->addError(
                'PEPPOL-EN16931-R041: '
                . $this->t->translate('PEPPOL.EN16931.R041'),
                $ac
            );
        }

        if (!$hasPercentage && $hasBaseAmount) {
            // Percentage required when base amount provided
            $this->addError(
                'PEPPOL-EN16931-R042: '
                . $this->t->translate('PEPPOL.EN16931.R042'),
                $ac
            );
        }

        if ($hasPercentage && $hasBaseAmount) {
            $this->validateAllowanceChargeCalculation($ac, $amount);
        }

        if ($chargeIndicator !== 'true'
            && $chargeIndicator !== 'false'
        ) {
            // Charge indicator must be true or false
            $this->addError(
                'PEPPOL-EN16931-R043: '
                . $this->t->translate('PEPPOL.EN16931.R043'),
                $ac
            );
        }
    }

    /**
     * @param DOMElement $ac Allowance/charge element
     * @param float $amount Amount value
     */
    private function validateAllowanceChargeCalculation(
        DOMElement $ac,
        float $amount
    ): void {
        $baseAmountNode = $this->getNodeValue('cbc:BaseAmount', $ac);
        $percentageNode = $this->getNodeValue(
            'cbc:MultiplierFactorNumeric',
            $ac
        );

        if ($baseAmountNode === null || $percentageNode === null) {
            return;
        }

        $baseAmount = (float)$baseAmountNode;
        $percentage = (float)$percentageNode;
        $calculatedAmount = ($baseAmount * $percentage) / 100.0;

        if (abs($calculatedAmount - $amount) > 0.02) {
            // Amount must equal base * percentage/100
            $this->addError(
                'PEPPOL-EN16931-R040: '
                . $this->t->translate('PEPPOL.EN16931.R040'),
                $ac
            );
        }
    }

    private function validatePriceLevelAllowances(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $priceAllowances = $this->xpath
            ->query('//cac:Price/cac:AllowanceCharge');

        if ($priceAllowances === false) {
            return;
        }

        foreach ($priceAllowances as $pa) {
            if (!($pa instanceof DOMElement)) {
                continue;
            }

            $chargeIndicator = $this->getNodeValue(
                'cbc:ChargeIndicator',
                $pa
            );

            if ($chargeIndicator !== 'false') {
                // Price level charge not allowed
                $this->addError(
                    'PEPPOL-EN16931-R044: '
                    . $this->t->translate('PEPPOL.EN16931.R044'),
                    $pa
                );
            }

            $this->validatePriceCalculation($pa);
        }
    }

    /**
     * @param DOMElement $pa Price allowance element
     */
    private function validatePriceCalculation(DOMElement $pa): void
    {
        $baseAmountNode = $this->getNodeValue('cbc:BaseAmount', $pa);
        $allowanceAmountNode = $this->getNodeValue('cbc:Amount', $pa);
        $priceAmountNode = $this->getNodeValue(
            '../cbc:PriceAmount',
            $pa
        );

        $baseAmount = $baseAmountNode !== null
            ? (float)$baseAmountNode
            : 0.0;
        $allowanceAmount = $allowanceAmountNode !== null
            ? (float)$allowanceAmountNode
            : 0.0;
        $priceAmount = $priceAmountNode !== null
            ? (float)$priceAmountNode
            : 0.0;

        $expected = $baseAmount - $allowanceAmount;

        if ($baseAmount > 0 && abs($priceAmount - $expected) > 0.01) {
            // Item net price calculation error
            $this->addError(
                'PEPPOL-EN16931-R046: '
                . $this->t->translate('PEPPOL.EN16931.R046'),
                $pa
            );
        }
    }

    private function validatePaymentMeans(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $directDebitMeans = $this->xpath->query(
            "//cac:PaymentMeans[cbc:PaymentMeansCode='49' "
            . "or cbc:PaymentMeansCode='59']"
        );

        if ($directDebitMeans === false) {
            return;
        }

        foreach ($directDebitMeans as $dd) {
            if (!($dd instanceof DOMElement)) {
                continue;
            }

            $mandateID = $this->getNodeValue(
                'cac:PaymentMandate/cbc:ID',
                $dd
            );

            if ($mandateID === null) {
                // Mandate reference required for direct debit
                $this->addError(
                    'PEPPOL-EN16931-R061: '
                    . $this->t->translate('PEPPOL.EN16931.R061'),
                    $dd
                );
            }
        }
    }

    private function validateCodeLists(): void
    {
        $this->validateCurrencyCodeLists();
        $this->validateCountryCodeList();
        $this->validateMimeCodeList();
        $this->validateAllowanceChargeReasonCodes();
        $this->validateInvoicePeriodDescriptionCode();
        $this->validateEndpointSchemeIDs();
        $this->validateEndpointSchemeFormats();
    }

    private function validateCurrencyCodeLists(): void
    {
        if ($this->documentCurrencyCode !== null
            && !CodeList::contains(CodeLists::ISO4217, $this->documentCurrencyCode)
        ) {
            $this->addError(
                'BR-CL-04          : '
                . $this->t->translate('BR.CL.04'),
                $this->getNode('//cbc:DocumentCurrencyCode')
            );
        }

        $taxCurrency = $this->getNodeValue(self::XPATH_TAX_CURRENCY_CODE);
        if ($taxCurrency !== null
            && !CodeList::contains(CodeLists::ISO4217, $taxCurrency)
        ) {
            $this->addError(
                'BR-CL-05          : '
                . $this->t->translate('BR.CL.05'),
                $this->getNode(self::XPATH_TAX_CURRENCY_CODE)
            );
        }
    }

    private function validateCountryCodeList(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $nodes = $this->xpath->query(
            '//cac:Country/cbc:IdentificationCode'
        );

        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $value = trim((string) $node->nodeValue);
            if ($value !== '' && !CodeList::contains(CodeLists::ISO3166, $value)) {
                $this->addError(
                    'BR-CL-14          : '
                    . $this->t->translate('BR.CL.14'),
                    $node
                );
            }
        }
    }

    private function validateMimeCodeList(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $nodes = $this->xpath->query(
            '//cac:Attachment/cbc:EmbeddedDocumentBinaryObject'
        );

        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $mime = $node->getAttribute('mimeCode');
            if ($mime !== '' && !CodeList::contains(CodeLists::MIME, $mime)) {
                $this->addError(
                    'BR-CL-24          : '
                    . $this->t->translate('BR.CL.24'),
                    $node
                );
            }
        }
    }

    private function validateAllowanceChargeReasonCodes(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $nodes = $this->xpath->query('//cac:AllowanceCharge');

        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $reasonCode = $this->getNodeValue(
                'cbc:AllowanceChargeReasonCode',
                $node
            );

            if ($reasonCode === null) {
                continue;
            }

            $isCharge = $this->getNodeValue(
                'cbc:ChargeIndicator',
                $node
            ) === 'true';

            if ($isCharge
                && !CodeList::contains(CodeLists::UNCL7161, $reasonCode)
            ) {
                $this->addError(
                    'BR-CL-21: '
                    . $this->t->translate('BR.CL.21'),
                    $node
                );
            } elseif (!$isCharge
                && !CodeList::contains(CodeLists::UNCL5189, $reasonCode)
            ) {
                $this->addError(
                    'BR-CL-20: '
                    . $this->t->translate('BR.CL.20'),
                    $node
                );
            }
        }
    }

    private function validateInvoicePeriodDescriptionCode(): void
    {
        $code = $this->getNodeValue(
            '//cac:InvoicePeriod/cbc:DescriptionCode'
        );

        if ($code !== null && !CodeList::contains(CodeLists::UNCL2005, $code)) {
            $this->addError(
                'BR-CL-23: '
                . $this->t->translate('BR.CL.23'),
                $this->getNode('//cac:InvoicePeriod/cbc:DescriptionCode')
            );
        }
    }

    private function validateEndpointSchemeIDs(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $nodes = $this->xpath->query('//cbc:EndpointID');

        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $schemeID = $node->getAttribute('schemeID');
            if ($schemeID !== ''
                && !CodeList::contains(CodeLists::EAID, $schemeID)
            ) {
                $this->addError(
                    'PEPPOL-CL-0008: '
                    . $this->t->translate('PEPPOL.CL.0008'),
                    $node
                );
            }
        }
    }

    private function validateCurrency(): void
    {
        if ($this->xpath === null
            || $this->documentCurrencyCode === null
        ) {
            return;
        }

        $amountXPath = '//cbc:Amount[@currencyID] | '
            . '//cbc:BaseAmount[@currencyID] | '
            . '//cbc:PriceAmount[@currencyID] | '
            . '//cac:TaxTotal[cac:TaxSubtotal]/'
            . 'cbc:TaxAmount[@currencyID] | '
            . '//cac:TaxSubtotal/cbc:TaxAmount[@currencyID] | '
            . '//cbc:TaxableAmount[@currencyID] | '
            . '//cbc:LineExtensionAmount[@currencyID] | '
            . '//cbc:TaxExclusiveAmount[@currencyID] | '
            . '//cbc:TaxInclusiveAmount[@currencyID] | '
            . '//cbc:AllowanceTotalAmount[@currencyID] | '
            . '//cbc:ChargeTotalAmount[@currencyID] | '
            . '//cbc:PrepaidAmount[@currencyID] | '
            . '//cbc:PayableRoundingAmount[@currencyID] | '
            . '//cbc:PayableAmount[@currencyID]';

        $amounts = $this->xpath->query($amountXPath);

        if ($amounts === false) {
            return;
        }

        foreach ($amounts as $amount) {
            if (!($amount instanceof DOMElement)) {
                continue;
            }
            $this->validateAmountCurrency($amount);
        }
    }

    /**
     * @param DOMElement $amount Amount element
     */
    private function validateAmountCurrency(DOMElement $amount): void
    {
        $currencyID = $amount->getAttribute('currencyID');

        if ($currencyID === $this->documentCurrencyCode) {
            return;
        }

        if ($amount->nodeName === 'cbc:TaxAmount'
            && $amount->parentNode !== null
            && $amount->parentNode->nodeName === 'cac:TaxTotal'
            && $this->xpath !== null
        ) {
            $subtotals = $this->xpath->query(
                'cac:TaxSubtotal',
                $amount->parentNode
            );

            if ($subtotals !== false && $subtotals->length === 0) {
                return;
            }
        }

        // All amounts must use document currency
        $this->addError(
            'PEPPOL-EN16931-R051: '
            . $this->t->translate('PEPPOL.EN16931.R051'),
            $amount
        );
    }

    /**
     * Get node value from XPath
     *
     * @param string $xpath XPath expression
     * @param DOMNode|null $contextNode Context node
     * @return string|null Node value
     */
    private function getNodeValue(
        string $xpath,
        ?DOMNode $contextNode = null
    ): ?string {
        if ($this->xpath === null) {
            return null;
        }

        $nodes = $contextNode !== null
            ? $this->xpath->query($xpath, $contextNode)
            : $this->xpath->query($xpath);

        $first     = ($nodes !== false && $nodes->length > 0)
            ? $nodes->item(0) : null;
        $nodeValue = $first?->nodeValue;

        return $nodeValue !== null ? trim($nodeValue) : null;
    }

    /**
     * Get node from XPath (for line number tracking)
     *
     * @param string $xpath XPath expression
     * @param DOMNode|null $contextNode Context node
     * @return DOMNode|null The node
     */
    private function getNode(
        string $xpath,
        ?DOMNode $contextNode = null
    ): ?DOMNode {
        if ($this->xpath === null) {
            return null;
        }

        $nodes = $contextNode !== null
            ? $this->xpath->query($xpath, $contextNode)
            : $this->xpath->query($xpath);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);

        // Ensure we only return DOMNode, not DOMNameSpaceNode
        return ($node instanceof DOMNode) ? $node : null;
    }

    private function addWarning(
        string $message,
        ?DOMNode $node = null
    ): void {
        $this->warnings[] = [
            'message'  => $message,
            'line'     => $node?->getLineNo(),
            'xpath'    => $node !== null ? $this->getNodeXPath($node) : null,
        ];
    }

    // ── PEPPOL-COMMON-R040–R053: endpoint / party ID format checks ────────

    private function validateEndpointSchemeFormats(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $nodes = $this->xpath->query(
            '//cbc:EndpointID[@schemeID] | '
            . '//cac:PartyIdentification/cbc:ID[@schemeID] | '
            . '//cbc:CompanyID[@schemeID]'
        );

        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            $this->applySchemeFormatRule($node);
        }
    }

    private function applySchemeFormatRule(DOMElement $node): void
    {
        $schemeID = $node->getAttribute('schemeID');
        $value    = trim((string) $node->nodeValue);
        if ($value === '') {
            return;
        }
        $this->applyFatalSchemeRules($schemeID, $value, $node);
        $this->applyWarningSchemeRules($schemeID, $value, $node);
    }

    private function applyFatalSchemeRules(
        string $schemeID, string $value, DOMElement $node
    ): void {
        if ($schemeID === '0088' && !$this->checkGLN($value)) {
            $this->addError('PEPPOL-COMMON-R040: '
                . $this->t->translate('peppol.common.r040'), $node);
        } elseif ($schemeID === '0192' && !$this->checkMod11($value)) {
            $this->addError('PEPPOL-COMMON-R041: '
                . $this->t->translate('peppol.common.r041'), $node);
        } elseif ($schemeID === '0208' && !$this->checkMod97BE($value)) {
            $this->addError('PEPPOL-COMMON-R043: '
                . $this->t->translate('peppol.common.r043'), $node);
        } elseif ($schemeID === '0007' && !$this->checkSEOrgnr($value)) {
            $this->addError('PEPPOL-COMMON-R049: '
                . $this->t->translate('peppol.common.r049'), $node);
        } elseif ($schemeID === '0151' && !$this->checkABN($value)) {
            $this->addError('PEPPOL-COMMON-R050: '
                . $this->t->translate('peppol.common.r050'), $node);
        }
    }

    private function applyWarningSchemeRules(
        string $schemeID, string $value, DOMElement $node
    ): void {
        if ($schemeID === '0184' && !$this->checkDanishCVR($value)) {
            $this->addWarning('PEPPOL-COMMON-R042: '
                . $this->t->translate('peppol.common.r042'), $node);
        } elseif ($schemeID === '0201' && !$this->checkCodiceIPA($value)) {
            $this->addWarning('PEPPOL-COMMON-R044: '
                . $this->t->translate('peppol.common.r044'), $node);
        } elseif ($schemeID === '0210' && !$this->checkCF($value)) {
            $this->addWarning('PEPPOL-COMMON-R045: '
                . $this->t->translate('peppol.common.r045'), $node);
        } elseif ($schemeID === '9907' && !$this->checkCF($value)) {
            $this->addWarning('PEPPOL-COMMON-R046: '
                . $this->t->translate('peppol.common.r046'), $node);
        } elseif ($schemeID === '0211' && !$this->checkPIVAseIT($value)) {
            $this->addWarning('PEPPOL-COMMON-R047: '
                . $this->t->translate('peppol.common.r047'), $node);
        } elseif ($schemeID === '0096' && !$this->checkDanishCC($value)) {
            $this->addWarning('PEPPOL-COMMON-R052: '
                . $this->t->translate('peppol.common.r052'), $node);
        } elseif ($schemeID === '0198' && !$this->checkDanishERSTORG($value)) {
            $this->addWarning('PEPPOL-COMMON-R053: '
                . $this->t->translate('peppol.common.r053'), $node);
        }
    }

    private function checkGLN(string $val): bool
    {
        if (!preg_match('/^\d+$/', $val)) {
            return false;
        }
        $len        = strlen($val);
        $checkDigit = (int) $val[$len - 1];
        $main       = array_reverse(str_split(substr($val, 0, $len - 1)));
        $sum        = 0;
        foreach ($main as $i => $d) {
            $sum += (int) $d * (1 + (($i + 1) % 2) * 2);
        }
        return (10 - ($sum % 10)) % 10 === $checkDigit;
    }

    private function checkMod11(string $val): bool
    {
        if (!preg_match('/^\d+$/', $val) || (float) $val <= 0) {
            return false;
        }
        $len        = strlen($val);
        $checkDigit = (int) $val[$len - 1];
        $main       = array_reverse(str_split(substr($val, 0, $len - 1)));
        $sum        = 0;
        foreach ($main as $i => $d) {
            $sum += (int) $d * (($i % 6) + 2);
        }
        return (11 - ($sum % 11)) % 11 === $checkDigit;
    }

    private function checkMod97BE(string $val): bool
    {
        if (!preg_match(self::REGEX_10_DIGITS, $val)) {
            return false;
        }
        $check      = (int) substr($val, 8, 2);
        $calculated = 97 - ((int) substr($val, 0, 8) % 97);
        return $check === $calculated;
    }

    private function checkDanishCVR(string $val): bool
    {
        if (strlen($val) === 10 && str_starts_with($val, 'DK')) {
            return (bool) preg_match('/^DK\d{8}$/', $val);
        }
        return (bool) preg_match('/^\d{8}$/', $val);
    }

    private function checkDanishCC(string $val): bool
    {
        return (bool) preg_match(self::REGEX_10_DIGITS, $val);
    }

    private function checkDanishERSTORG(string $val): bool
    {
        return (bool) preg_match('/^DK\d{8}$/', $val);
    }

    private function checkCodiceIPA(string $val): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{6}$/', $val);
    }

    private function checkItalianPIVA(string $val): int
    {
        /** @var array<int,int> $doubleMap */
        $doubleMap = [0, 2, 4, 6, 8, 1, 3, 5, 7, 9];
        $sum = 0;
        $len = strlen($val);
        for ($i = 0; $i < $len; $i++) {
            $d    = (int) $val[$i];
            $sum += ($i % 2 === 0) ? $d : $doubleMap[$d];
        }
        return $sum % 10;
    }

    private function checkCF(string $val): bool
    {
        $val = trim($val);
        if (strlen($val) === 16) {
            return (bool) preg_match(
                '/^[A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z]$/i',
                $val
            );
        }
        if (strlen($val) === 11 && preg_match(self::REGEX_11_DIGITS, $val)) {
            return $this->checkItalianPIVA($val) === 0;
        }
        return false;
    }

    private function checkPIVAseIT(string $val): bool
    {
        return preg_match(self::REGEX_11_DIGITS, $val) === 1
            && $this->checkItalianPIVA($val) === 0;
    }

    private function checkSEOrgnr(string $val): bool
    {
        if (!preg_match(self::REGEX_10_DIGITS, $val)) {
            return false;
        }
        $main       = strrev(substr($val, 0, 9));
        $checkDigit = (int) $val[9];
        $sum        = 0;
        for ($i = 0; $i < 9; $i++) {
            $n = (int) $main[$i];
            if ($i % 2 === 0) {
                $d    = $n * 2;
                $sum += ($d % 10) + intdiv($d, 10);
            } else {
                $sum += $n;
            }
        }
        return (10 - $sum % 10) % 10 === $checkDigit;
    }

    
    private function checkABN(string $val): bool
    {
        if (!preg_match(self::REGEX_11_DIGITS, $val)) {
            return false;
        }
        /** @var array<int<0,10>, int> $weights */
        $weights = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];
        $digits  = str_split($val);
        $digits[0] = (string) ((int) $digits[0] - 1);
        $sum = 0;
        for ($i = 0; $i <= 10; $i++) {
            $sum += (int) $digits[$i] * $weights[$i];
        }
        return $sum % 89 === 0;
    }

    private function getXMLErrors(): string
    {
        $errors = libxml_get_errors();
        $result = [];

        foreach ($errors as $error) {
            $result[] = trim($error->message);
        }

        libxml_clear_errors();
        return implode('; ', $result);
    }

    /**
     * Add an error with line number tracking
     *
     * @param string $message Error message
     * @param DOMNode|null $node The node where error occurred
     * @param string|null $xpath XPath to the element (optional)
     */
    private function addError(
        string $message,
        ?DOMNode $node = null,
        ?string $xpath = null
    ): void {
        $lineNo = null;
        $computedXPath = $xpath;

        if ($node !== null) {
            $lineNo = (string) $node->getLineNo();

            // Try to build XPath if not provided
            if ($computedXPath === null) {
                $computedXPath = $this->getNodeXPath($node);
            }
        }

        /**
         * @psalm-suppress PropertyTypeCoercion $this->errors
         */
        $this->errors[] = [
            'rule' => substr($message, 0,19),
            'text' => substr($message, 20, strlen($message)),
            'line' => $lineNo,
            'xpath' => $computedXPath,
        ];
    }

    /** @param DOMNode $node The node */
    private function getNodeXPath(DOMNode $node): string
    {
        return XPathHelper::buildPath($node);
    }

/**
 * @return array<int, array{rule: string, text: string, line: string|null, xpath: string|null}>
 */
    public function getErrors(): array
    {
        return $this->errors;
    }

/**
 * @return array<int, array{message: string, line: int|null, xpath: string|null}>
 */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

/**
 * @return array<string, mixed>
 */
    public function getSummary(): array
    {
        return [
            'valid' => $this->isValid(),
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'document_type' => $this->documentType,
            'profile' => $this->profile,
            'supplier_country' => $this->supplierCountry,
            'customer_country' => $this->customerCountry,
        ];
    }

    /**
     * Get errors formatted for display
     *
     * @return array<int, string>
     */
    public function getFormattedErrors(): array
    {
        $formatted = [];

        foreach ($this->errors as $error) {
            $msg = $error['rule'] . ' ' . $error['text'];

            if ($error['line'] !== null) {
                $msg = "[Line {$error['line']}] " . $msg;
            }

            if ($error['xpath'] !== null) {
                $msg .= " (at {$error['xpath']})";
            }

            $formatted[] = $msg;
        }

        return $formatted;
    }

    /**
     * Get warnings formatted for display
     *
     * @return array<int, string>
     */
    public function getFormattedWarnings(): array
    {
        $formatted = [];

        foreach ($this->warnings as $warning) {
            $msg = $warning['message'];

            if ($warning['line'] !== null) {
                $msg = "[Line {$warning['line']}] " . $msg;
            }

            if ($warning['xpath'] !== null) {
                $msg .= " (at {$warning['xpath']})";
            }

            $formatted[] = $msg;
        }

        return $formatted;
    }
}
