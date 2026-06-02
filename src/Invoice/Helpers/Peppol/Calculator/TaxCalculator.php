<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Calculator;

use DOMElement;

/**
 * Validates VAT category tax amount rules.
 *
 * Rules covered:
 *   BR-S-08 — Standard-rate (S): TaxAmount = round(TaxableAmount × Percent / 100, 2)
 *   BR-Z-08 — Zero-rated (Z): TaxAmount must be 0
 *   BR-E-08 — Exempt (E): TaxAmount must be 0
 */
class TaxCalculator extends AbstractCalculator
{
    #[\Override]
    public function validate(): void
    {
        $subtotals = $this->xpath->query('//cac:TaxTotal/cac:TaxSubtotal');
        if ($subtotals === false) {
            return;
        }

        foreach ($subtotals as $subtotal) {
            if (!($subtotal instanceof DOMElement)) {
                continue;
            }

            $categoryId = $this->getNodeValue('cac:TaxCategory/cbc:ID', $subtotal);

            match ($categoryId) {
                'S'     => $this->validateBrS08($subtotal),
                'Z'     => $this->validateBrZ08($subtotal),
                'E'     => $this->validateBrE08($subtotal),
                default => null,
            };
        }
    }

    /**
     * BR-S-08: Standard rate — TaxAmount must equal TaxableAmount × Percent / 100 (±TOLERANCE).
     */
    private function validateBrS08(DOMElement $subtotal): void
    {
        $taxAmount = $this->getNodeValue('cbc:TaxAmount', $subtotal);
        $taxable   = $this->getNodeValue('cbc:TaxableAmount', $subtotal);
        $percent   = $this->getNodeValue('cac:TaxCategory/cbc:Percent', $subtotal);

        if ($taxAmount === null || $taxable === null || $percent === null) {
            return;
        }

        $calculated = round((float) $taxable * (float) $percent / 100.0, 2);

        if (abs($calculated - (float) $taxAmount) > self::TOLERANCE) {
            $this->addError(
                'BR-S-08           : ' . $this->t->translate('BR.S.08'),
                $subtotal
            );
        }
    }

    /**
     * BR-Z-08: Zero-rated — TaxAmount must be 0.
     */
    private function validateBrZ08(DOMElement $subtotal): void
    {
        $taxAmount = $this->getNodeValue('cbc:TaxAmount', $subtotal);
        if ($taxAmount !== null && abs((float) $taxAmount) > self::TOLERANCE) {
            $this->addError(
                'BR-Z-08           : ' . $this->t->translate('BR.Z.08'),
                $subtotal
            );
        }
    }

    /**
     * BR-E-08: Exempt — TaxAmount must be 0.
     */
    private function validateBrE08(DOMElement $subtotal): void
    {
        $taxAmount = $this->getNodeValue('cbc:TaxAmount', $subtotal);
        if ($taxAmount !== null && abs((float) $taxAmount) > self::TOLERANCE) {
            $this->addError(
                'BR-E-08           : ' . $this->t->translate('BR.E.08'),
                $subtotal
            );
        }
    }
}
