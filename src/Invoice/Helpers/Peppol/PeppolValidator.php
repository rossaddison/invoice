<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Ast\ChecksumAlgorithm;
use App\Invoice\Helpers\Peppol\Ast\ExpressionEvaluator;
use App\Invoice\Helpers\Peppol\Calculator\InvoiceLineCalculator;
use App\Invoice\Helpers\Peppol\Calculator\MonetaryTotalCalculator;
use App\Invoice\Helpers\Peppol\Calculator\TaxCalculator;
use App\Invoice\Helpers\Peppol\Rule\EN16931\PEPPOL_EN16931_R001;
use App\Invoice\Helpers\Peppol\Rule\EN16931\PEPPOL_EN16931_R002;
use App\Invoice\Helpers\Peppol\Rule\EN16931\PEPPOL_EN16931_R003;
use App\Invoice\Helpers\Peppol\Rule\RuleRegistry;
use App\Invoice\Helpers\Peppol\Rule\Severity;
use App\Invoice\Helpers\Peppol\Rule\ValidationContext;
use App\Invoice\Helpers\Peppol\SchematronParser;
use App\Invoice\Helpers\Peppol\SchematronRuleRunner;
use App\Invoice\Helpers\Peppol\Validator\AllowanceChargeValidator;
use App\Invoice\Helpers\Peppol\Validator\ChecksumValidator;
use App\Invoice\Helpers\Peppol\Validator\CodeListValidator;
use App\Invoice\Helpers\Peppol\Validator\CurrencyValidator;
use App\Invoice\Helpers\Peppol\Validator\DocumentLevelValidator;
use App\Invoice\Helpers\Peppol\Validator\EndpointSchemeValidator;
use App\Invoice\Helpers\Peppol\Validator\TaxTotalValidator;
use Yiisoft\Aliases\Aliases;
use App\Invoice\Helpers\Peppol\XPathHelper;
use DOMDocument;
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

    private const string XPATH_PROFILE_ID     = '//cbc:ProfileID';
    private const string XPATH_SUPPLIER_PARTY = '//cac:AccountingSupplierParty/cac:Party/';
    private const string XPATH_CUSTOMER_PARTY = '//cac:AccountingCustomerParty/cac:Party/';
    private const string XPATH_TAX_SCHEME_VAT = "cac:PartyTaxScheme[cac:TaxScheme/cbc:ID='VAT']/";

    /** @var array<string, string> */
    private array $namespaces = [
        'cbc'            => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
        'cac'            => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'ubl-invoice'    => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        'ubl-creditnote' => 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2',
    ];

    private ?string $profile              = null;
    private ?string $supplierCountry      = null;
    private ?string $customerCountry      = null;
    private ?string $documentCurrencyCode = null;
    private ?string $documentType         = null;

    public function __construct(
        private readonly TranslatorInterface $t
    ) {
        $this->dom = new DOMDocument();
        $this->dom->preserveWhiteSpace = false;
    }

    /**
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

    /**
     * Run all validations.
     *
     * @return bool True if valid
     */
    public function validate(): bool
    {
        $this->errors   = [];
        $this->warnings = [];

        if ($this->documentType === null) {
            $this->addError($this->t->translate('peppol.unknown.document.type'));
            return false;
        }

        // When the Schematron file is present the runner covers all business rules.
        // Skip the hand-written sub-validators to avoid duplicate errors.
        if ($this->xpath !== null && !is_file(self::schPath())) {
            $subValidators = [
                new DocumentLevelValidator($this->xpath, $this->t, $this->profile),
                new TaxTotalValidator($this->xpath, $this->t, $this->documentCurrencyCode, $this->documentType),
                new AllowanceChargeValidator($this->xpath, $this->t),
                new CurrencyValidator($this->xpath, $this->t, $this->documentCurrencyCode),
                new CodeListValidator($this->xpath, $this->t, $this->documentCurrencyCode),
                new EndpointSchemeValidator($this->xpath, $this->t),
            ];
            foreach ($subValidators as $v) {
                $v->validate();
                array_push($this->errors, ...$v->getErrors());
                array_push($this->warnings, ...$v->getWarnings());
            }
        }

        // Calculators run regardless — arithmetic/rounding checks complement the Schematron.
        $this->validateWithCalculators();
        $this->validateWithRegistry();

        return empty($this->errors);
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

    private function extractDocumentVariables(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $invoices    = $this->xpath->query('//ubl-invoice:Invoice');
        $creditNotes = $this->xpath->query('//ubl-creditnote:CreditNote');

        if ($invoices !== false && $invoices->length > 0) {
            $this->documentType = 'Invoice';
        } elseif ($creditNotes !== false && $creditNotes->length > 0) {
            $this->documentType = 'CreditNote';
        }

        $profileID = $this->getNodeValue(self::XPATH_PROFILE_ID);
        if ($profileID !== null) {
            $pattern = '/urn:fdc:peppol\.eu:2017:poacc:billing:(\d{2}):1\.0/';
            if (preg_match($pattern, $profileID, $matches) === 1) {
                $this->profile = $matches[1];
            } else {
                $this->profile = $this->t->translate('reason.unknown');
            }
        } else {
            $this->profile = $this->t->translate('reason.unknown');
        }

        $this->supplierCountry      = $this->extractSupplierCountry();
        $this->customerCountry      = $this->extractCustomerCountry();
        $this->documentCurrencyCode = $this->getNodeValue('//cbc:DocumentCurrencyCode');
    }

    private function extractSupplierCountry(): string
    {
        $vatPath    = self::XPATH_SUPPLIER_PARTY . self::XPATH_TAX_SCHEME_VAT . 'cbc:CompanyID';
        $vatCountry = $this->getNodeValue($vatPath);

        if ($vatCountry !== null && strlen($vatCountry) >= 2) {
            return strtoupper(substr($vatCountry, 0, 2));
        }

        $taxRepPath    = '//cac:TaxRepresentativeParty/' . self::XPATH_TAX_SCHEME_VAT . 'cbc:CompanyID';
        $taxRepCountry = $this->getNodeValue($taxRepPath);

        if ($taxRepCountry !== null && strlen($taxRepCountry) >= 2) {
            return strtoupper(substr($taxRepCountry, 0, 2));
        }

        $addressPath = self::XPATH_SUPPLIER_PARTY . 'cac:PostalAddress/cac:Country/cbc:IdentificationCode';
        $country     = $this->getNodeValue($addressPath);

        return $country !== null ? strtoupper($country) : 'XX';
    }

    private function extractCustomerCountry(): string
    {
        $vatPath        = self::XPATH_CUSTOMER_PARTY . self::XPATH_TAX_SCHEME_VAT . 'cbc:CompanyID';
        $custVatCountry = $this->getNodeValue($vatPath);

        if ($custVatCountry !== null && strlen($custVatCountry) >= 2) {
            return strtoupper(substr($custVatCountry, 0, 2));
        }

        $addressPath = self::XPATH_CUSTOMER_PARTY . 'cac:PostalAddress/cac:Country/cbc:IdentificationCode';
        $country     = $this->getNodeValue($addressPath);

        return $country !== null ? strtoupper($country) : 'XX';
    }

    private static function schPath(): string
    {
        $aliases = new Aliases(['@peppol' => dirname(__DIR__, 4) . '/resources/peppol']);
        return $aliases->get('@peppol') . '/PEPPOL-EN16931-UBL.sch';
    }

    /**
     * Run all Schematron rules via SchematronRuleRunner when the .sch file is present,
     * falling back to the three hand-written EN16931 rules otherwise.
     *
     * The parsed SchematronDocument is cached statically so the file is only read
     * and parsed once per PHP process, regardless of how many invoices are validated.
     */
    private function validateWithRegistry(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $violations = is_file(self::schPath())
            ? $this->runSchematron()
            : $this->runHandwrittenRules();

        foreach ($violations as $v) {
            if ($v->severity === Severity::Warning || $v->severity === Severity::Info) {
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

    /** @return array<int, \App\Invoice\Helpers\Peppol\Rule\ValidationViolation> */
    private function runSchematron(): array
    {
        /** @var \App\Invoice\Helpers\Peppol\SchematronDocument|null $schDoc */
        static $schDoc = null;
        if ($schDoc === null) {
            $schDoc = (new SchematronParser())->parseFile(self::schPath());
        }

        return (new SchematronRuleRunner(
            new ExpressionEvaluator($this->checksumHandlers())
        ))->run($schDoc, $this->dom);
    }

    /** @return array<int, \App\Invoice\Helpers\Peppol\Rule\ValidationViolation> */
    private function runHandwrittenRules(): array
    {
        if ($this->xpath === null) {
            return [];
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

        return $registry->run($this->xpath, $context);
    }

    /**
     * Map each ChecksumAlgorithm to the corresponding ChecksumValidator static method.
     *
     * @return array<string, callable(string): bool>
     */
    private function checksumHandlers(): array
    {
        return [
            ChecksumAlgorithm::GLN->value           => ChecksumValidator::checkGLN(...),
            ChecksumAlgorithm::Mod11->value         => ChecksumValidator::checkMod11(...),
            ChecksumAlgorithm::Mod97BE->value       => ChecksumValidator::checkMod97BE(...),
            ChecksumAlgorithm::SEOrgnr->value       => ChecksumValidator::checkSEOrgnr(...),
            ChecksumAlgorithm::ABN->value           => ChecksumValidator::checkABN(...),
            ChecksumAlgorithm::CodiceFiscale->value => ChecksumValidator::checkCF(...),
            ChecksumAlgorithm::PIVAseIT->value      => ChecksumValidator::checkPIVAseIT(...),
            ChecksumAlgorithm::CodiceIPA->value     => ChecksumValidator::checkCodiceIPA(...),
            ChecksumAlgorithm::DanishCVR->value     => ChecksumValidator::checkDanishCVR(...),
        ];
    }

    /**
     * Delegate arithmetic checks to dedicated calculator classes and merge their errors.
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

    private function getNodeValue(string $xpath, ?DOMNode $contextNode = null): ?string
    {
        if ($this->xpath === null) {
            return null;
        }

        $nodes = $contextNode !== null
            ? $this->xpath->query($xpath, $contextNode)
            : $this->xpath->query($xpath);

        $first     = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;
        $nodeValue = $first?->nodeValue;

        return $nodeValue !== null ? trim($nodeValue) : null;
    }

    private function addError(string $message, ?DOMNode $node = null): void
    {
        $lineNo        = null;
        $computedXPath = null;

        if ($node !== null) {
            $lineNo        = (string) $node->getLineNo();
            $computedXPath = $this->getNodeXPath($node);
        }

        $parts          = explode(': ', $message, 2);
        $this->errors[] = [
            'rule'  => rtrim($parts[0]),
            'text'  => $parts[1] ?? '',
            'line'  => $lineNo,
            'xpath' => $computedXPath,
        ];
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

    private function getNodeXPath(DOMNode $node): string
    {
        return XPathHelper::buildPath($node);
    }
}
