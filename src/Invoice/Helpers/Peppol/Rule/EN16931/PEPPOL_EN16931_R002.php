<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Rule\EN16931;

use App\Invoice\Helpers\Peppol\Rule\AbstractRule;
use App\Invoice\Helpers\Peppol\Rule\ValidationContext;
use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;
use DOMNode;
use DOMXPath;
use Yiisoft\Translator\TranslatorInterface;

/**
 * PEPPOL-EN16931-R002: A maximum of one Note is allowed on the document level,
 * unless both supplier and customer are in Germany (DE-to-DE relaxation).
 *
 * Schematron equivalent:
 *   <assert id="PEPPOL-EN16931-R002" flag="fatal"
 *           test="count(cbc:Note) &lt;= 1 or (... DE-to-DE condition ...)">
 *     No more than one Note is allowed on document level.
 *   </assert>
 */
final class PEPPOL_EN16931_R002 extends AbstractRule // NOSONAR php:S101 — name mirrors schematron rule ID
{
    public function __construct(private readonly TranslatorInterface $t) {}

    #[\Override]
    public function id(): string
    {
        return 'PEPPOL-EN16931-R002';
    }

    #[\Override]
    public function validate(DOMXPath $xpath, ValidationContext $context): array
    {
        $notes = $xpath->query('//cbc:Note');
        if ($notes === false || $notes->length <= 1) {
            return [];
        }

        $supplierDE = ($context->supplierCountry === 'DE');
        $customerDE = ($context->customerCountry === 'DE');
        if ($supplierDE && $customerDE) {
            return [];
        }

        $second = $notes->item(1);
        return [$this->fatal(
            $this->t->translate('PEPPOL.EN16931.R002'),
            ($second instanceof DOMNode) ? $second : null
        )];
    }
}
