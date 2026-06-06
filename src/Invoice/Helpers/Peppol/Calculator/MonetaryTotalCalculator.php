<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Calculator;

/**
 * Validates document-level monetary total consistency rules.
 *
 * Rules covered:
 *   BR-CO-10 — Line net sum − doc allowances + doc charges = TaxExclusiveAmount
 *   BR-CO-13 — TaxExclusiveAmount + VAT = TaxInclusiveAmount
 *   BR-CO-14 — TaxInclusiveAmount − prepaid + rounding = PayableAmount
 *   BR-CO-15 — Sum of VAT-category taxable amounts = TaxExclusiveAmount
 *   BR-CO-16 — Sum of VAT-category tax amounts = total TaxAmount
 */
class MonetaryTotalCalculator extends AbstractCalculator
{
    private const string LMT = '//cac:LegalMonetaryTotal/';

    #[\Override]
    public function validate(): void
    {
        $this->validateBrCo10();
        $this->validateBrCo13();
        $this->validateBrCo14();
        $this->validateBrCo15();
        $this->validateBrCo16();
    }

    /**
     * BR-CO-10: Σ(line net amounts) − Σ(doc allowances) + Σ(doc charges) = TaxExclusiveAmount
     */
    private function validateBrCo10(): void
    {
        $declared = $this->getNodeValue(self::LMT . 'cbc:TaxExclusiveAmount');
        if ($declared === null) {
            return;
        }

        $lineSum = $this->sumXPath(
            '//cac:InvoiceLine/cbc:LineExtensionAmount'
            . ' | //cac:CreditNoteLine/cbc:LineExtensionAmount'
        );

        // Document-level only: exclude amounts nested inside invoice/credit-note lines
        $allowanceSum = $this->sumXPath(
            "//cac:AllowanceCharge"
            . "[not(ancestor::cac:InvoiceLine)]"
            . "[not(ancestor::cac:CreditNoteLine)]"
            . "[cbc:ChargeIndicator='false']/cbc:Amount"
        );
        $chargeSum = $this->sumXPath(
            "//cac:AllowanceCharge"
            . "[not(ancestor::cac:InvoiceLine)]"
            . "[not(ancestor::cac:CreditNoteLine)]"
            . "[cbc:ChargeIndicator='true']/cbc:Amount"
        );

        $calculated = $lineSum - $allowanceSum + $chargeSum;

        if (abs($calculated - (float) $declared) > self::TOLERANCE) {
            $this->addError(
                'BR-CO-10          : ' . $this->t->translate('BR.CO.10'),
                $this->getNode(self::LMT . 'cbc:TaxExclusiveAmount')
            );
        }
    }

    /**
     * BR-CO-13: TaxExclusiveAmount + Σ(TaxAmount with subtotals) = TaxInclusiveAmount
     */
    private function validateBrCo13(): void
    {
        $taxExcl = $this->getNodeValue(self::LMT . 'cbc:TaxExclusiveAmount');
        $taxIncl = $this->getNodeValue(self::LMT . 'cbc:TaxInclusiveAmount');

        if ($taxExcl === null || $taxIncl === null) {
            return;
        }

        $taxAmountSum = $this->sumXPath('//cac:TaxTotal[cac:TaxSubtotal]/cbc:TaxAmount');
        $calculated   = (float) $taxExcl + $taxAmountSum;

        if (abs($calculated - (float) $taxIncl) > self::TOLERANCE) {
            $this->addError(
                'BR-CO-13          : ' . $this->t->translate('BR.CO.13'),
                $this->getNode(self::LMT . 'cbc:TaxInclusiveAmount')
            );
        }
    }

    /**
     * BR-CO-14: TaxInclusiveAmount − PrepaidAmount + PayableRoundingAmount = PayableAmount
     * PrepaidAmount and PayableRoundingAmount default to 0 when absent.
     */
    private function validateBrCo14(): void
    {
        $taxIncl = $this->getNodeValue(self::LMT . 'cbc:TaxInclusiveAmount');
        $payable = $this->getNodeValue(self::LMT . 'cbc:PayableAmount');

        if ($taxIncl === null || $payable === null) {
            return;
        }

        $prepaid  = (float) ($this->getNodeValue(self::LMT . 'cbc:PrepaidAmount')         ?? '0');
        $rounding = (float) ($this->getNodeValue(self::LMT . 'cbc:PayableRoundingAmount')  ?? '0');
        $calculated = (float) $taxIncl - $prepaid + $rounding;

        if (abs($calculated - (float) $payable) > self::TOLERANCE) {
            $this->addError(
                'BR-CO-14          : ' . $this->t->translate('BR.CO.14'),
                $this->getNode(self::LMT . 'cbc:PayableAmount')
            );
        }
    }

    /**
     * BR-CO-15: Σ(TaxSubtotal/TaxableAmount) = TaxExclusiveAmount
     */
    private function validateBrCo15(): void
    {
        $taxExcl = $this->getNodeValue(self::LMT . 'cbc:TaxExclusiveAmount');
        if ($taxExcl === null) {
            return;
        }

        $subtotalSum = $this->sumXPath('//cac:TaxTotal/cac:TaxSubtotal/cbc:TaxableAmount');

        if (abs($subtotalSum - (float) $taxExcl) > self::TOLERANCE) {
            $this->addError(
                'BR-CO-15          : ' . $this->t->translate('BR.CO.15'),
                $this->getNode(self::LMT . 'cbc:TaxExclusiveAmount')
            );
        }
    }

    /**
     * BR-CO-16: Σ(TaxSubtotal/TaxAmount) = TaxTotal/TaxAmount (the one with subtotals)
     */
    private function validateBrCo16(): void
    {
        $totalTax = $this->getNodeValue('//cac:TaxTotal[cac:TaxSubtotal]/cbc:TaxAmount');
        if ($totalTax === null) {
            return;
        }

        $subtotalSum = $this->sumXPath(
            '//cac:TaxTotal[cac:TaxSubtotal]/cac:TaxSubtotal/cbc:TaxAmount'
        );

        if (abs($subtotalSum - (float) $totalTax) > self::TOLERANCE) {
            $this->addError(
                'BR-CO-16          : ' . $this->t->translate('BR.CO.16'),
                $this->getNode('//cac:TaxTotal[cac:TaxSubtotal]/cbc:TaxAmount')
            );
        }
    }
}
