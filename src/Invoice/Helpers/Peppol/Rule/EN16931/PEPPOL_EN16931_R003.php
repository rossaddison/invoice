<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Rule\EN16931;

use App\Invoice\Helpers\Peppol\Rule\AbstractRule;
use App\Invoice\Helpers\Peppol\Rule\ValidationContext;
use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;
use DOMXPath;
use Yiisoft\Translator\TranslatorInterface;

/**
 * PEPPOL-EN16931-R003: A buyer reference (BuyerReference) or order reference
 * (OrderReference/ID) MUST be provided.
 *
 * Schematron equivalent:
 *   <assert id="PEPPOL-EN16931-R003" flag="fatal"
 *           test="cbc:BuyerReference or cac:OrderReference/cbc:ID">
 *     A buyer reference or order reference MUST be provided.
 *   </assert>
 */
final class PEPPOL_EN16931_R003 extends AbstractRule // NOSONAR php:S101 — name mirrors schematron rule ID
{
    public function __construct(private readonly TranslatorInterface $t) {}

    #[\Override]
    public function id(): string
    {
        return 'PEPPOL-EN16931-R003';
    }

    #[\Override]
    public function validate(DOMXPath $xpath, ValidationContext $context): array
    {
        $buyerRef = $this->queryValue($xpath, '//cbc:BuyerReference');
        $orderRef = $this->queryValue($xpath, '//cac:OrderReference/cbc:ID');

        if ($buyerRef !== null || $orderRef !== null) {
            return [];
        }

        return [$this->fatal(
            $this->t->translate('PEPPOL.EN16931.R003'),
            $this->queryNode($xpath, '//cbc:BuyerReference')
        )];
    }
}
