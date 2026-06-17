<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Validator;

use App\Invoice\Helpers\Peppol\Calculator\AbstractCalculator;
use DOMElement;
use DOMXPath;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Validates PEPPOL-EN16931-R051: all monetary amounts must use the document currency.
 */
class CurrencyValidator extends AbstractCalculator
{
    private const string AMOUNT_XPATH =
        '//cbc:Amount[@currencyID] | '
        . '//cbc:BaseAmount[@currencyID] | '
        . '//cbc:PriceAmount[@currencyID] | '
        . '//cac:TaxTotal[cac:TaxSubtotal]/cbc:TaxAmount[@currencyID] | '
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
        if ($this->documentCurrencyCode === null) {
            return;
        }

        $amounts = $this->xpath->query(self::AMOUNT_XPATH);
        if ($amounts === false) {
            return;
        }

        foreach ($amounts as $amount) {
            if ($amount instanceof DOMElement) {
                $this->validateAmountCurrency($amount);
            }
        }
    }

    private function validateAmountCurrency(DOMElement $amount): void
    {
        $currencyID = $amount->getAttribute('currencyID');

        if ($currencyID === $this->documentCurrencyCode) {
            return;
        }

        if ($amount->nodeName === 'cbc:TaxAmount'
            && $amount->parentNode !== null
            && $amount->parentNode->nodeName === 'cac:TaxTotal'
        ) {
            $subtotals = $this->xpath->query('cac:TaxSubtotal', $amount->parentNode);
            if ($subtotals !== false && $subtotals->length === 0) {
                return;
            }
        }

        $this->addError(
            'PEPPOL-EN16931-R051: ' . $this->t->translate('PEPPOL.EN16931.R051'),
            $amount
        );
    }
}
