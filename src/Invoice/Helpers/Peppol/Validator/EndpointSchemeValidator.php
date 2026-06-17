<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Validator;

use App\Invoice\Helpers\Peppol\Calculator\AbstractCalculator;
use DOMElement;

/**
 * Validates PEPPOL-COMMON-R040–R053: endpoint and party ID scheme format rules.
 */
class EndpointSchemeValidator extends AbstractCalculator
{
    #[\Override]
    public function validate(): void
    {
        $nodes = $this->xpath->query(
            '//cbc:EndpointID[@schemeID] | '
            . '//cac:PartyIdentification/cbc:ID[@schemeID] | '
            . '//cbc:CompanyID[@schemeID]'
        );

        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $this->applySchemeFormatRule($node);
            }
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

    private function applyFatalSchemeRules(string $schemeID, string $value, DOMElement $node): void
    {
        if ($schemeID === '0088' && !ChecksumValidator::checkGLN($value)) {
            $this->addError('PEPPOL-COMMON-R040: ' . $this->t->translate('PEPPOL.COMMON.R040'), $node);
        } elseif ($schemeID === '0192' && !ChecksumValidator::checkMod11($value)) {
            $this->addError('PEPPOL-COMMON-R041: ' . $this->t->translate('PEPPOL.COMMON.R041'), $node);
        } elseif ($schemeID === '0208' && !ChecksumValidator::checkMod97BE($value)) {
            $this->addError('PEPPOL-COMMON-R043: ' . $this->t->translate('PEPPOL.COMMON.R043'), $node);
        } elseif ($schemeID === '0007' && !ChecksumValidator::checkSEOrgnr($value)) {
            $this->addError('PEPPOL-COMMON-R049: ' . $this->t->translate('PEPPOL.COMMON.R049'), $node);
        } elseif ($schemeID === '0151' && !ChecksumValidator::checkABN($value)) {
            $this->addError('PEPPOL-COMMON-R050: ' . $this->t->translate('PEPPOL.COMMON.R050'), $node);
        }
    }

    private function applyWarningSchemeRules(string $schemeID, string $value, DOMElement $node): void
    {
        if ($schemeID === '0184' && !ChecksumValidator::checkDanishCVR($value)) {
            $this->addWarning('PEPPOL-COMMON-R042: ' . $this->t->translate('PEPPOL.COMMON.R042'), $node);
        } elseif ($schemeID === '0201' && !ChecksumValidator::checkCodiceIPA($value)) {
            $this->addWarning('PEPPOL-COMMON-R044: ' . $this->t->translate('PEPPOL.COMMON.R044'), $node);
        } elseif ($schemeID === '0210' && !ChecksumValidator::checkCF($value)) {
            $this->addWarning('PEPPOL-COMMON-R045: ' . $this->t->translate('PEPPOL.COMMON.R045'), $node);
        } elseif ($schemeID === '9907' && !ChecksumValidator::checkCF($value)) {
            $this->addWarning('PEPPOL-COMMON-R046: ' . $this->t->translate('PEPPOL.COMMON.R046'), $node);
        } elseif ($schemeID === '0211' && !ChecksumValidator::checkPIVAseIT($value)) {
            $this->addWarning('PEPPOL-COMMON-R047: ' . $this->t->translate('PEPPOL.COMMON.R047'), $node);
        } elseif ($schemeID === '0096' && !ChecksumValidator::checkDanishCC($value)) {
            $this->addWarning('PEPPOL-COMMON-R052: ' . $this->t->translate('PEPPOL.COMMON.R052'), $node);
        } elseif ($schemeID === '0198' && !ChecksumValidator::checkDanishERSTORG($value)) {
            $this->addWarning('PEPPOL-COMMON-R053: ' . $this->t->translate('PEPPOL.COMMON.R053'), $node);
        }
    }
}
