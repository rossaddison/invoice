<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Validator;

use App\Invoice\Helpers\Peppol\Calculator\AbstractCalculator;
use DOMElement;
use DOMXPath;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Validates document-level structure: UBL version, profile, customization ID,
 * empty elements, party endpoints, and payment-means mandate reference.
 */
class DocumentLevelValidator extends AbstractCalculator
{
    private const string XPATH_PROFILE_ID       = '//cbc:ProfileID';
    private const string XPATH_CUSTOMIZATION_ID = '//cbc:CustomizationID';
    private const string XPATH_SUPPLIER_PARTY   = '//cac:AccountingSupplierParty/cac:Party/';
    private const string XPATH_CUSTOMER_PARTY   = '//cac:AccountingCustomerParty/cac:Party/';
    private const array  UBL_ACCEPTED_VERSIONS  = ['2.1', '2.2', '2.3', '2.4'];

    public function __construct(
        DOMXPath $xpath,
        TranslatorInterface $t,
        private readonly ?string $profile,
    ) {
        parent::__construct($xpath, $t);
    }

    #[\Override]
    public function validate(): void
    {
        $this->validateUBLVersion();
        $this->validateProfile();
        $this->validateCustomizationID();
        $this->validateEmptyElements();
        $this->validateParties();
        $this->validatePaymentMeans();
    }

    private function validateUBLVersion(): void
    {
        $version = $this->getNodeValue('//cbc:UBLVersionID');
        if ($version !== null && !in_array($version, self::UBL_ACCEPTED_VERSIONS, true)) {
            $this->addError(
                'UBL-VERSION: ' . $this->t->translate('ubl.version.required.2.4') . ' (' . $version . ')',
                $this->getNode('//cbc:UBLVersionID')
            );
        }
    }

    private function validateProfile(): void
    {
        if ($this->profile === $this->t->translate('reason.unknown')) {
            $this->addError(
                'PEPPOL-EN16931-R007: ' . $this->t->translate('PEPPOL.EN16931.R007')
                . "'urn:fdc:peppol.eu:2017:poacc:billing:NN:1.0'",
                $this->getNode(self::XPATH_PROFILE_ID)
            );
        }
    }

    private function validateCustomizationID(): void
    {
        $customizationID = $this->getNodeValue(self::XPATH_CUSTOMIZATION_ID);
        $requiredStart   = 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0';

        if ($customizationID === null) {
            $this->addError(
                'PEPPOL-EN16931-R004: ' . $this->t->translate('PEPPOL.EN16931.R004.REQUIRED'),
                $this->getNode(self::XPATH_CUSTOMIZATION_ID)
            );
            return;
        }

        if (!str_starts_with($customizationID, $requiredStart)) {
            $this->addError(
                'PEPPOL-EN16931-R004: ' . $this->t->translate('PEPPOL.EN16931.R004.INVALID'),
                $this->getNode(self::XPATH_CUSTOMIZATION_ID)
            );
        }
    }

    private function validateEmptyElements(): void
    {
        $allElements = $this->xpath->query('//*');
        if ($allElements === false) {
            return;
        }

        foreach ($allElements as $element) {
            if (!($element instanceof DOMElement)) {
                continue;
            }
            $nodeValue = $element->nodeValue;
            if (!$element->hasChildNodes() && ($nodeValue === null || trim($nodeValue) === '')) {
                $this->addError(
                    'PEPPOL-EN16931-R008: ' . $this->t->translate('PEPPOL.EN16931.R008') . $element->nodeName,
                    $element
                );
            }
        }
    }

    private function validateParties(): void
    {
        if ($this->getNodeValue(self::XPATH_CUSTOMER_PARTY . 'cbc:EndpointID') === null) {
            $this->addError(
                'PEPPOL-EN16931-R010: ' . $this->t->translate('PEPPOL.EN16931.R010'),
                $this->getNode(self::XPATH_CUSTOMER_PARTY . 'cbc:EndpointID')
            );
        }

        if ($this->getNodeValue(self::XPATH_SUPPLIER_PARTY . 'cbc:EndpointID') === null) {
            $this->addError(
                'PEPPOL-EN16931-R020: ' . $this->t->translate('PEPPOL.EN16931.R020'),
                $this->getNode(self::XPATH_SUPPLIER_PARTY . 'cbc:EndpointID')
            );
        }
    }

    private function validatePaymentMeans(): void
    {
        $directDebitMeans = $this->xpath->query(
            "//cac:PaymentMeans[cbc:PaymentMeansCode='49' or cbc:PaymentMeansCode='59']"
        );

        if ($directDebitMeans === false) {
            return;
        }

        foreach ($directDebitMeans as $dd) {
            if (!($dd instanceof DOMElement)) {
                continue;
            }
            if ($this->getNodeValue('cac:PaymentMandate/cbc:ID', $dd) === null) {
                $this->addError(
                    'PEPPOL-EN16931-R061: ' . $this->t->translate('PEPPOL.EN16931.R061'),
                    $dd
                );
            }
        }
    }
}
