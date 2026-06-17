<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Validator;

use App\Invoice\Helpers\Peppol\Calculator\AbstractCalculator;
use DOMElement;

/**
 * Validates PEPPOL-EN16931-R040–R046: allowance/charge and price-level rules.
 */
class AllowanceChargeValidator extends AbstractCalculator
{
    #[\Override]
    public function validate(): void
    {
        $allowanceCharges = $this->xpath->query('//cac:AllowanceCharge');
        if ($allowanceCharges !== false) {
            foreach ($allowanceCharges as $ac) {
                if ($ac instanceof DOMElement) {
                    $this->validateSingleAllowanceCharge($ac);
                }
            }
        }

        $this->validatePriceLevelAllowances();
    }

    private function validateSingleAllowanceCharge(DOMElement $ac): void
    {
        $hasPercentage   = $this->getNodeValue('cbc:MultiplierFactorNumeric', $ac) !== null;
        $hasBaseAmount   = $this->getNodeValue('cbc:BaseAmount', $ac) !== null;
        $amountNode      = $this->getNodeValue('cbc:Amount', $ac);
        $amount          = $amountNode !== null ? (float) $amountNode : 0.0;
        $chargeIndicator = $this->getNodeValue('cbc:ChargeIndicator', $ac);

        if ($hasPercentage && !$hasBaseAmount) {
            $this->addError('PEPPOL-EN16931-R041: ' . $this->t->translate('PEPPOL.EN16931.R041'), $ac);
        }

        if (!$hasPercentage && $hasBaseAmount) {
            $this->addError('PEPPOL-EN16931-R042: ' . $this->t->translate('PEPPOL.EN16931.R042'), $ac);
        }

        if ($hasPercentage && $hasBaseAmount) {
            $this->validateAllowanceChargeCalculation($ac, $amount);
        }

        if ($chargeIndicator !== 'true' && $chargeIndicator !== 'false') {
            $this->addError('PEPPOL-EN16931-R043: ' . $this->t->translate('PEPPOL.EN16931.R043'), $ac);
        }
    }

    private function validateAllowanceChargeCalculation(DOMElement $ac, float $amount): void
    {
        $baseAmountNode = $this->getNodeValue('cbc:BaseAmount', $ac);
        $percentageNode = $this->getNodeValue('cbc:MultiplierFactorNumeric', $ac);

        if ($baseAmountNode === null || $percentageNode === null) {
            return;
        }

        $calculated = ((float) $baseAmountNode * (float) $percentageNode) / 100.0;

        if (abs($calculated - $amount) > 0.02) {
            $this->addError('PEPPOL-EN16931-R040: ' . $this->t->translate('PEPPOL.EN16931.R040'), $ac);
        }
    }

    private function validatePriceLevelAllowances(): void
    {
        $priceAllowances = $this->xpath->query('//cac:Price/cac:AllowanceCharge');
        if ($priceAllowances === false) {
            return;
        }

        foreach ($priceAllowances as $pa) {
            if (!($pa instanceof DOMElement)) {
                continue;
            }

            if ($this->getNodeValue('cbc:ChargeIndicator', $pa) !== 'false') {
                $this->addError('PEPPOL-EN16931-R044: ' . $this->t->translate('PEPPOL.EN16931.R044'), $pa);
            }

            $this->validatePriceCalculation($pa);
        }
    }

    private function validatePriceCalculation(DOMElement $pa): void
    {
        $baseAmount      = (float) ($this->getNodeValue('cbc:BaseAmount', $pa) ?? '0');
        $allowanceAmount = (float) ($this->getNodeValue('cbc:Amount', $pa) ?? '0');
        $priceAmount     = (float) ($this->getNodeValue('../cbc:PriceAmount', $pa) ?? '0');

        if ($baseAmount > 0 && abs($priceAmount - ($baseAmount - $allowanceAmount)) > 0.01) {
            $this->addError('PEPPOL-EN16931-R046: ' . $this->t->translate('PEPPOL.EN16931.R046'), $pa);
        }
    }
}
