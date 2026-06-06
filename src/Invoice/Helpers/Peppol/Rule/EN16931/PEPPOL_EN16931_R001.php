<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Rule\EN16931;

use App\Invoice\Helpers\Peppol\Rule\AbstractRule;
use App\Invoice\Helpers\Peppol\Rule\ValidationContext;
use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;
use DOMXPath;
use Yiisoft\Translator\TranslatorInterface;

/**
 * PEPPOL-EN16931-R001: A business process (ProfileID) MUST be provided.
 *
 * Schematron equivalent:
 *   <assert id="PEPPOL-EN16931-R001" flag="fatal"
 *           test="normalize-space(cbc:ProfileID) != ''">
 *     A business process MUST be provided.
 *   </assert>
 */
final class PEPPOL_EN16931_R001 extends AbstractRule // NOSONAR php:S101 — name mirrors schematron rule ID
{
    public function __construct(private readonly TranslatorInterface $t) {}

    #[\Override]
    public function id(): string
    {
        return 'PEPPOL-EN16931-R001';
    }

    #[\Override]
    public function validate(DOMXPath $xpath, ValidationContext $context): array
    {
        $value = $this->queryValue($xpath, '//cbc:ProfileID');
        if ($value !== null && $value !== '') {
            return [];
        }

        return [$this->fatal(
            $this->t->translate('PEPPOL.EN16931.R001'),
            $this->queryNode($xpath, '//cbc:ProfileID')
        )];
    }
}
