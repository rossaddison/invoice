<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Validator;

use App\Invoice\Helpers\Peppol\Calculator\AbstractCalculator;
use App\Invoice\Helpers\Peppol\CodeList;
use App\Invoice\Helpers\Peppol\CodeLists;
use DOMElement;
use DOMXPath;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Validates BR-CL-* code list rules (currency, country, MIME, allowance reason, endpoint scheme).
 */
class CodeListValidator extends AbstractCalculator
{
    private const string XPATH_TAX_CURRENCY_CODE = '//cbc:TaxCurrencyCode';

    public function __construct(
        DOMXPath $xpath,
        TranslatorInterface $t,
        private readonly ?string $documentCurrencyCode,
    ) {
        parent::__construct($xpath, $t);
    }

    #[\Override]
    public function validate(): void
    {
        $this->validateCurrencyCodeLists();
        $this->validateCountryCodeList();
        $this->validateMimeCodeList();
        $this->validateAllowanceChargeReasonCodes();
        $this->validateInvoicePeriodDescriptionCode();
        $this->validateEndpointSchemeIDs();
    }

    private function validateCurrencyCodeLists(): void
    {
        if ($this->documentCurrencyCode !== null
            && !CodeList::contains(CodeLists::ISO4217, $this->documentCurrencyCode)
        ) {
            $this->addError(
                'BR-CL-04          : ' . $this->t->translate('BR.CL.04'),
                $this->getNode('//cbc:DocumentCurrencyCode')
            );
        }

        $taxCurrency = $this->getNodeValue(self::XPATH_TAX_CURRENCY_CODE);
        if ($taxCurrency !== null && !CodeList::contains(CodeLists::ISO4217, $taxCurrency)) {
            $this->addError(
                'BR-CL-05          : ' . $this->t->translate('BR.CL.05'),
                $this->getNode(self::XPATH_TAX_CURRENCY_CODE)
            );
        }
    }

    private function validateCountryCodeList(): void
    {
        $nodes = $this->xpath->query('//cac:Country/cbc:IdentificationCode');
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            $value = trim((string) $node->nodeValue);
            if ($value !== '' && !CodeList::contains(CodeLists::ISO3166, $value)) {
                $this->addError('BR-CL-14          : ' . $this->t->translate('BR.CL.14'), $node);
            }
        }
    }

    private function validateMimeCodeList(): void
    {
        $nodes = $this->xpath->query('//cac:Attachment/cbc:EmbeddedDocumentBinaryObject');
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            $mime = $node->getAttribute('mimeCode');
            if ($mime !== '' && !CodeList::contains(CodeLists::MIME, $mime)) {
                $this->addError('BR-CL-24          : ' . $this->t->translate('BR.CL.24'), $node);
            }
        }
    }

    private function validateAllowanceChargeReasonCodes(): void
    {
        $nodes = $this->xpath->query('//cac:AllowanceCharge');
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $reasonCode = $this->getNodeValue('cbc:AllowanceChargeReasonCode', $node);
            if ($reasonCode === null) {
                continue;
            }

            $isCharge = $this->getNodeValue('cbc:ChargeIndicator', $node) === 'true';

            if ($isCharge && !CodeList::contains(CodeLists::UNCL7161, $reasonCode)) {
                $this->addError('BR-CL-21: ' . $this->t->translate('BR.CL.21'), $node);
            } elseif (!$isCharge && !CodeList::contains(CodeLists::UNCL5189, $reasonCode)) {
                $this->addError('BR-CL-20: ' . $this->t->translate('BR.CL.20'), $node);
            }
        }
    }

    private function validateInvoicePeriodDescriptionCode(): void
    {
        $code = $this->getNodeValue('//cac:InvoicePeriod/cbc:DescriptionCode');
        if ($code !== null && !CodeList::contains(CodeLists::UNCL2005, $code)) {
            $this->addError(
                'BR-CL-23: ' . $this->t->translate('BR.CL.23'),
                $this->getNode('//cac:InvoicePeriod/cbc:DescriptionCode')
            );
        }
    }

    private function validateEndpointSchemeIDs(): void
    {
        $nodes = $this->xpath->query('//cbc:EndpointID');
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            $schemeID = $node->getAttribute('schemeID');
            if ($schemeID !== '' && !CodeList::contains(CodeLists::EAID, $schemeID)) {
                $this->addError('PEPPOL-CL-0008: ' . $this->t->translate('PEPPOL.CL.0008'), $node);
            }
        }
    }
}
