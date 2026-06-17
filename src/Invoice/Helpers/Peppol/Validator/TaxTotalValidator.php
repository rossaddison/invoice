<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Validator;

use App\Invoice\Helpers\Peppol\Calculator\AbstractCalculator;
use DOMNode;
use DOMXPath;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Validates PEPPOL-EN16931-R053–R055, R005, R080: tax-total structure and sign rules.
 */
class TaxTotalValidator extends AbstractCalculator
{
    private const string XPATH_TAX_CURRENCY_CODE = '//cbc:TaxCurrencyCode';

    public function __construct(
        DOMXPath $xpath,
        TranslatorInterface $t,
        private readonly ?string $documentCurrencyCode,
        private readonly ?string $documentType,
    ) {
        parent::__construct($xpath, $t);
    }

    #[\Override]
    public function validate(): void
    {
        $taxCurrencyCode = $this->getNodeValue(self::XPATH_TAX_CURRENCY_CODE);

        $this->validateTaxTotalWithSubtotal();
        $this->validateTaxTotalWithoutSubtotal($taxCurrencyCode);
        $this->validateTaxAmountsSameSign($taxCurrencyCode);
        $this->validateTaxCurrencyNotEqualDocCurrency($taxCurrencyCode);
        $this->validateCreditNoteProjectReference();
    }

    private function validateTaxTotalWithSubtotal(): void
    {
        $taxTotalWithSub = $this->xpath->query('//cac:TaxTotal[cac:TaxSubtotal]');

        if ($taxTotalWithSub !== false && $taxTotalWithSub->length !== 1) {
            $node = $taxTotalWithSub->length > 0
                ? $taxTotalWithSub->item(1)
                : $this->getNode('//cac:TaxTotal');
            $this->addError(
                'PEPPOL-EN16931-R053: ' . $this->t->translate('PEPPOL.EN16931.R053'),
                ($node instanceof DOMNode) ? $node : null
            );
        }
    }

    private function validateTaxTotalWithoutSubtotal(?string $taxCurrencyCode): void
    {
        $taxTotalWithoutSub = $this->xpath->query('//cac:TaxTotal[not(cac:TaxSubtotal)]');
        if ($taxTotalWithoutSub === false) {
            return;
        }

        $expectedCount = $taxCurrencyCode !== null ? 1 : 0;

        if ($taxTotalWithoutSub->length !== $expectedCount) {
            $node = $taxTotalWithoutSub->length > 0 ? $taxTotalWithoutSub->item(0) : null;
            $this->addError(
                'PEPPOL-EN16931-R054: ' . $this->t->translate('PEPPOL.EN16931.R054'),
                ($node instanceof DOMNode) ? $node : null
            );
        }
    }

    private function validateTaxAmountsSameSign(?string $taxCurrencyCode): void
    {
        if ($taxCurrencyCode === null || $this->documentCurrencyCode === null) {
            return;
        }

        $docCurrPath = "//cac:TaxTotal/cbc:TaxAmount[@currencyID='{$this->documentCurrencyCode}']";
        $taxCurrPath = "//cac:TaxTotal/cbc:TaxAmount[@currencyID='{$taxCurrencyCode}']";

        $docNodes = $this->xpath->query($docCurrPath);
        $taxNodes = $this->xpath->query($taxCurrPath);

        $docItem = ($docNodes !== false) ? $docNodes->item(0) : null;
        $taxItem = ($taxNodes !== false) ? $taxNodes->item(0) : null;

        $taxAmountDoc = ($docItem instanceof DOMNode) ? (float) $docItem->nodeValue : null;
        $taxAmountTax = ($taxItem instanceof DOMNode) ? (float) $taxItem->nodeValue : null;

        if ($taxAmountDoc === null || $taxAmountTax === null) {
            return;
        }

        $differentSigns = ($taxAmountDoc < 0 && $taxAmountTax > 0)
            || ($taxAmountDoc > 0 && $taxAmountTax < 0);

        if ($differentSigns) {
            $this->addError(
                'PEPPOL-EN16931-R055: ' . $this->t->translate('PEPPOL.EN16931.R055'),
                ($docItem instanceof DOMNode) ? $docItem : null
            );
        }
    }

    private function validateTaxCurrencyNotEqualDocCurrency(?string $taxCurrencyCode): void
    {
        if ($taxCurrencyCode !== null
            && $this->documentCurrencyCode !== null
            && $taxCurrencyCode === $this->documentCurrencyCode
        ) {
            $this->addError(
                'PEPPOL-EN16931-R005: ' . $this->t->translate('PEPPOL.EN16931.R005'),
                $this->getNode(self::XPATH_TAX_CURRENCY_CODE)
            );
        }
    }

    private function validateCreditNoteProjectReference(): void
    {
        if ($this->documentType !== 'CreditNote') {
            return;
        }

        $projectRefs = $this->xpath->query(
            "//cac:AdditionalDocumentReference[cbc:DocumentTypeCode='50']"
        );

        if ($projectRefs !== false && $projectRefs->length > 1) {
            $node = $projectRefs->item(1);
            $this->addError(
                'PEPPOL-EN16931-R080: ' . $this->t->translate('PEPPOL.EN16931.R080'),
                ($node instanceof DOMNode) ? $node : null
            );
        }
    }
}
