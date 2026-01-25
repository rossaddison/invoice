<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

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

/** @var array<int, array{message: string, line: int|null, xpath: string|null}> */
    private array $errors = [];

/** @var array<int, array{message: string, line: int|null, xpath: string|null}> */
    private array $warnings = [];

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

    /** @var array<int, string> */
    private array $iso3166 = [];

    /** @var array<int, string> */
    private array $iso4217 = [];

    /** @var array<int, string> */
    private array $mimeCode = [];

    /** @var array<int, string> */
    private array $uncl2005 = [];

    /** @var array<int, string> */
    private array $uncl5189 = [];

    /** @var array<int, string> */
    private array $uncl7161 = [];

    /** @var array<int, string> */
    private array $eaid = [];

    private ?string $profile = null;
    private ?string $supplierCountry = null;
    private ?string $customerCountry = null;
    private ?string $documentCurrencyCode = null;
    private ?string $documentType = null;
    
    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(
        private readonly TranslatorInterface $t
    )
    {
        $this->dom = new DOMDocument();
        $this->dom->preserveWhiteSpace = false;
        $this->initializeCodeLists();
    }

    private function initializeCodeLists(): void
    {
        $this->iso3166 = explode(' ', 'AD AE AF AG AI AL AM AO AQ AR '
            . 'AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN '
            . 'BO BQ BR BS BT BV BW BY BZ CA CC CD CF CG CH CI CK CL '
            . 'CM CN CO CR CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE '
            . 'EG EH ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GG GH '
            . 'GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU '
            . 'ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI '
            . 'KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY '
            . 'MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU '
            . 'MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM '
            . 'PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS '
            . 'RU RW SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS '
            . 'ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT '
            . 'TV TW TZ UA UG UM US UY UZ VA VC VE VG VI VN VU WF WS '
            . 'YE YT ZA ZM ZW 1A XI');

        $this->iso4217 = explode(' ', 'AED AFN ALL AMD ANG AOA ARS '
            . 'AUD AWG AZN BAM BBD BDT BGN BHD BIF BMD BND BOB BOV '
            . 'BRL BSD BTN BWP BYN BZD CAD CDF CHE CHF CHW CLF CLP '
            . 'CNY COP COU CRC CUP CVE CZK DJF DKK DOP DZD EGP ERN '
            . 'ETB EUR FJD FKP GBP GEL GHS GIP GMD GNF GTQ GYD HKD '
            . 'HNL HTG HUF IDR ILS INR IQD IRR ISK JMD JOD JPY KES '
            . 'KGS KHR KMF KPW KRW KWD KYD KZT LAK LBP LKR LRD LSL '
            . 'LYD MAD MDL MGA MKD MMK MNT MOP MRU MUR MVR MWK MXN '
            . 'MXV MYR MZN NAD NGN NIO NOK NPR NZD OMR PAB PEN PGK '
            . 'PHP PKR PLN PYG QAR RON RSD RUB RWF SAR SBD SCR SDG '
            . 'SEK SGD SHP SLE SOS SRD SSP STN SVC SYP SZL THB TJS '
            . 'TMT TND TOP TRY TTD TWD TZS UAH UGX USD USN UYI UYU '
            . 'UYW UZS VED VES VND VUV WST XAF XAG XAU XBA XBB XBC '
            . 'XBD XCD XDR XOF XPD XPF XPT XSU XTS XUA YER ZAR ZMW '
            . 'ZWG XXX');

        $this->mimeCode = [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'text/csv',
            'application/vnd.openxmlformats-officedocument.'
                . 'spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.spreadsheet',
        ];

        $this->uncl2005 = ['3', '35', '432'];

        $this->uncl5189 = ['41', '42', '60', '62', '63', '64', '65',
            '66', '67', '68', '70', '71', '88', '95', '100', '102',
            '103', '104', '105'];

        $this->uncl7161 = explode(' ', 'AA AAA AAC AAD AAE AAF AAH '
            . 'AAI AAS AAT AAV AAY AAZ ABA ABB ABC ABD ABF ABK ABL '
            . 'ABN ABR ABS ABT ABU ACF ACG ACH ACI ACJ ACK ACL ACM '
            . 'ACS ADC ADE ADJ ADK ADL ADM ADN ADO ADP ADQ ADR ADT '
            . 'ADW ADY ADZ AEA AEB AEC AED AEF AEH AEI AEJ AEK AEL '
            . 'AEM AEN AEO AEP AES AET AEU AEV AEW AEX AEY AEZ AJ '
            . 'AU CA CAB CAD CAE CAF CAI CAJ CAK CAL CAM CAN CAO '
            . 'CAP CAQ CAR CAS CAT CAU CAV CAW CAX CAY CAZ CD CG '
            . 'CS CT DAB DAC DAD DAF DAG DAH DAI DAJ DAK DAL DAM '
            . 'DAN DAO DAP DAQ DL EG EP ER FAA FAB FAC FC FH FI '
            . 'GAA HAA HD HH IAA IAB ID IF IR IS KO L1 LA LAA LAB '
            . 'LF MAE MI ML NAA OA PA PAA PC PL PRV RAB RAC RAD '
            . 'RAF RE RF RH RV SA SAA SAD SAE SAI SG SH SM SU TAB '
            . 'TAC TT TV V1 V2 WH XAA YY ZZZ');

        $this->eaid = explode(' ', '0002 0007 0009 0037 0060 0088 '
            . '0096 0097 0106 0130 0135 0142 0151 0177 0183 0184 '
            . '0188 0190 0191 0192 0193 0195 0196 0198 0199 0200 '
            . '0201 0202 0204 0208 0209 0210 0211 0212 0213 0215 '
            . '0216 0218 0221 0230 0235 9910 9913 9914 9915 9918 '
            . '9919 9920 9922 9923 9924 9925 9926 9927 9928 9929 '
            . '9930 9931 9932 9933 9934 9935 9936 9937 9938 9939 '
            . '9940 9941 9942 9943 9944 9945 9946 9947 9948 9949 '
            . '9950 9951 9952 9953 9957 9959 0147 0154 0158 0170 '
            . '0194 0203 0205 0217 0225 0240');
    }

    /**
     * Load XML content
     *
     * @param string $xmlContent XML to validate
     * @return bool Success status
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

        $profileID = $this->getNodeValue('//cbc:ProfileID');
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
        $vatPath = "//cac:AccountingSupplierParty/cac:Party/"
            . "cac:PartyTaxScheme[cac:TaxScheme/cbc:ID='VAT']/"
            . "cbc:CompanyID";
        $vatCountry = $this->getNodeValue($vatPath);

        if ($vatCountry !== null && strlen($vatCountry) >= 2) {
            return strtoupper(substr($vatCountry, 0, 2));
        }

        $taxRepPath = "//cac:TaxRepresentativeParty/"
            . "cac:PartyTaxScheme[cac:TaxScheme/cbc:ID='VAT']/"
            . "cbc:CompanyID";
        $taxRepCountry = $this->getNodeValue($taxRepPath);

        if ($taxRepCountry !== null && strlen($taxRepCountry) >= 2) {
            return strtoupper(substr($taxRepCountry, 0, 2));
        }

        $addressPath = '//cac:AccountingSupplierParty/cac:Party/'
            . 'cac:PostalAddress/cac:Country/'
            . 'cbc:IdentificationCode';
        $country = $this->getNodeValue($addressPath);

        return $country !== null ? strtoupper($country) : 'XX';
    }

    private function extractCustomerCountry(): string
    {
        $vatPath = "//cac:AccountingCustomerParty/cac:Party/"
            . "cac:PartyTaxScheme[cac:TaxScheme/cbc:ID='VAT']/"
            . "cbc:CompanyID";
        $custVatCountry = $this->getNodeValue($vatPath);

        if ($custVatCountry !== null && strlen($custVatCountry) >= 2) {
            return strtoupper(substr($custVatCountry, 0, 2));
        }

        $addressPath = '//cac:AccountingCustomerParty/cac:Party/'
            . 'cac:PostalAddress/cac:Country/'
            . 'cbc:IdentificationCode';
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

        $this->validateEmptyElements();
        $this->validateDocumentLevel();
        $this->validateParties();
        $this->validateAllowanceCharge();
        $this->validatePaymentMeans();
        $this->validateCurrency();

        return empty($this->errors);
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
        if ($this->getNodeValue('//cbc:ProfileID') === null) {
            // Business process MUST be provided
            $this->addError(
                'PEPPOL-EN16931-R001: '
                . $this->t->translate('PEPPOL.EN16931.R001'),
                $this->getNode('//cbc:ProfileID')
            );
        }

        if ($this->profile === 'Unknown') {
            // Business process MUST be in format
            $this->addError(
                'PEPPOL-EN16931-R007: '
                . $this->t->translate('PEPPOL.EN16931.R007')
                . "'urn:fdc:peppol.eu:2017:poacc:billing:NN:1.0'",
                $this->getNode('//cbc:ProfileID')
            );
        }

        $this->validateNoteRestrictions();
        $this->validateBuyerReference();
        $this->validateCustomizationID();
        $this->validateTaxTotals();
    }

    private function validateNoteRestrictions(): void
    {
        if ($this->xpath === null) {
            return;
        }

        $supplierDE = ($this->supplierCountry === 'DE');
        $customerDE = ($this->customerCountry === 'DE');

        $notes = $this->xpath->query('//cbc:Note');
        if ($notes === false) {
            return;
        }

        $noteCount = $notes->length;

        if ($noteCount > 1 && !($supplierDE && $customerDE)) {
            $node = $notes->item(1); // Second note
            $domNode = ($node instanceof DOMNode) ? $node : null;
            // Max one note allowed unless both parties are DE
            $this->addError(
            'PEPPOL-EN16931-R002: ' . $this->t->translate('PEPPOL.EN16931.R002'),
                $domNode
            );
        }
    }

    private function validateBuyerReference(): void
    {
        $buyerRef = $this->getNodeValue('//cbc:BuyerReference');
        $orderRef = $this->getNodeValue('//cac:OrderReference/cbc:ID');

        if ($buyerRef === null && $orderRef === null) {
            // Buyer reference or order reference required
            $this->addError(
            'PEPPOL-EN16931-R003: ' . $this->t->translate('PEPPOL.EN16931.R003'),
                $this->getNode('//cbc:BuyerReference')
            );
        }
    }

    private function validateCustomizationID(): void
    {
        $customizationID = $this->getNodeValue(
            '//cbc:CustomizationID'
        );

        $requiredStart = 'urn:cen.eu:en16931:2017#compliant#'
            . 'urn:fdc:peppol.eu:2017:poacc:billing:3.0';

        if ($customizationID === null) {
            // Specification identifier required
            $this->addError(
                'PEPPOL-EN16931-R004: ' .
                $this->t->translate('PEPPOL.EN16931.R004.REQUIRED'),
                $this->getNode('//cbc:CustomizationID')
            );
            return;
        }

        if (!str_starts_with($customizationID, $requiredStart)) {
            // Invalid specification identifier format
            $this->addError(
                'PEPPOL-EN16931-R004: '
                . $this->t->translate('PEPPOL.EN16931.R004.INVALID'),
                $this->getNode('//cbc:CustomizationID')
            );
        }
    }

    private function validateTaxTotals(): void
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
            $domNode = ($node instanceof DOMNode) ? $node : null;
            // One tax total with subtotals required
            $this->addError(
                'PEPPOL-EN16931-R053: '
                . $this->t->translate('PEPPOL.EN16931.R053'),
                $domNode
            );
        }

        $taxCurrencyCode = $this->getNodeValue(
            '//cbc:TaxCurrencyCode'
        );

        $taxTotalWithoutSub = $this->xpath
            ->query('//cac:TaxTotal[not(cac:TaxSubtotal)]');

        if ($taxTotalWithoutSub !== false) {
            $expectedCount = $taxCurrencyCode !== null ? 1 : 0;

            if ($taxTotalWithoutSub->length !== $expectedCount) {
                $node = $taxTotalWithoutSub->length > 0
                    ? $taxTotalWithoutSub->item(0)
                    : null;
                $domNode = ($node instanceof DOMNode) ? $node : null;
                // Invalid tax total without subtotals count 
                $this->addError(
                    'PEPPOL-EN16931-R054: '
                    . $this->t->translate('PEPPOL.EN16931.R054'),
                    $domNode
                );
            }
        }

        $this->validateTaxAmountsSameSign($taxCurrencyCode);

        if ($taxCurrencyCode !== null
            && $this->documentCurrencyCode !== null
            && $taxCurrencyCode === $this->documentCurrencyCode
        ) {
            // Tax currency must differ from document currency
            $this->addError(
                'PEPPOL-EN16931-R005: '
                . $this->t->translate('PEPPOL.EN16931.R005'),
                $this->getNode('//cbc:TaxCurrencyCode')
            );
        }

        if ($this->documentType === 'CreditNote') {
            $projectRefs = $this->xpath->query(
                "//cac:AdditionalDocumentReference"
                . "[cbc:DocumentTypeCode='50']"
            );

            if ($projectRefs !== false && $projectRefs->length > 1) {
                $node = $projectRefs->item(1);
                $domNode = ($node instanceof DOMNode) ? $node : null;
                // Max one project reference allowed
                $this->addError(
                    'PEPPOL-EN16931-R080: '
                    . $this->t->translate('PEPPOL.EN16931.R080'),
                    $domNode
                );
            }
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

        $docNode = $this->xpath->query($docCurrPath);
        $taxNode = $this->xpath->query($taxCurrPath);

        if ($docNode === false || $taxNode === false) {
            return;
        }

        $docItem = $docNode->item(0);
        $taxItem = $taxNode->item(0);

        if ($docItem === null || $taxItem === null) {
            return;
        }

        $docValue = $docItem->nodeValue;
        $taxValue = $taxItem->nodeValue;

        if ($docValue === null || $taxValue === null) {
            return;
        }

        $taxAmountDoc = (float)$docValue;
        $taxAmountTax = (float)$taxValue;

        $differentSigns = ($taxAmountDoc < 0 && $taxAmountTax > 0)
            || ($taxAmountDoc > 0 && $taxAmountTax < 0);

        if ($differentSigns) {
            $domNode = ($docItem instanceof DOMNode) ? $docItem : null;
            // Tax amounts must have same sign
            $this->addError(
                'PEPPOL-EN16931-R055: '
                . $this->t->translate('PEPPOL.EN16931.R055'),
                $domNode
            );
        }
    }

    private function validateParties(): void
    {
        $buyerEndpoint = $this->getNodeValue(
            '//cac:AccountingCustomerParty/cac:Party/'
            . 'cbc:EndpointID'
        );

        if ($buyerEndpoint === null) {
            $this->addError(
                'PEPPOL-EN16931-R010: Buyer electronic address required',
                $this->getNode('//cac:AccountingCustomerParty/cac:Party/cbc:'
                        . 'EndpointID')
            );
        }

        $sellerEndpoint = $this->getNodeValue(
            '//cac:AccountingSupplierParty/cac:Party/'
            . 'cbc:EndpointID'
        );

        if ($sellerEndpoint === null) {
            $this->addError(
                'PEPPOL-EN16931-R020: Seller electronic address required',
                $this->getNode('//cac:AccountingSupplierParty/cac:Party/cbc:'
                        . 'EndpointID')
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

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = $nodes->item(0);
        if ($value === null) {
            return null;
        }

        $nodeValue = $value->nodeValue;
        if ($nodeValue === null) {
            return null;
        }

        return trim($nodeValue);
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

    /**
     * Add a warning with line number tracking
     *
     * @param string $message Warning message
     * @param DOMNode|null $node The node where warning occurred
     * @param string|null $xpath XPath to the element (optional)
     */
    private function addWarning(
        string $message,
        ?DOMNode $node = null,
        ?string $xpath = null
    ): void {
        $lineNo = null;
        $computedXPath = $xpath;

        if ($node !== null) {
            $lineNo = $node->getLineNo();
            
            if ($computedXPath === null) {
                $computedXPath = $this->getNodeXPath($node);
            }
        }

        $this->warnings[] = [
            'message' => $message,
            'line' => $lineNo,
            'xpath' => $computedXPath,
        ];
    }

    /**
     * Build XPath for a given node
     *
     * @param DOMNode $node The node
     * @return string XPath expression
     */
    private function getNodeXPath(DOMNode $node): string
    {
        $path = '';
        
        while ($node !== null && $node->nodeType === XML_ELEMENT_NODE) {
            $nodeName = $node->nodeName;
            
            // Count preceding siblings with same name
            $position = 1;
            $sibling = $node->previousSibling;
            while ($sibling !== null) {
                if ($sibling->nodeType === XML_ELEMENT_NODE 
                    && $sibling->nodeName === $nodeName
                ) {
                    $position++;
                }
                $sibling = $sibling->previousSibling;
            }
            
            $path = "/{$nodeName}[{$position}]" . $path;
            $node = $node->parentNode;
        }
        
        return $path ?: '/';
    }

/**
 * @return array<int, array{message: string, line: int|null, xpath: string|null}>
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
            $msg = $error['message'];
            
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
