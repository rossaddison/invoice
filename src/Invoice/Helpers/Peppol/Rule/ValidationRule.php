<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Rule;

use DOMXPath;

/**
 * Contract for a single Peppol / EN16931 validation rule.
 *
 * Each concrete implementation maps 1-to-1 with a Schematron assert or report,
 * e.g. PEPPOL_EN16931_R001 ↔ assert id="PEPPOL-EN16931-R001".
 *
 * Keeping rules as independent classes means:
 *  - each rule is testable in isolation with a minimal DOMXPath fixture;
 *  - adding or removing a rule requires no changes to PeppolValidator itself,
 *    only to the RuleRegistry registration list;
 *  - severity and message are decided per-rule, not in a monolithic switch.
 */
interface ValidationRule
{
    /**
     * The canonical Peppol rule identifier, e.g. 'PEPPOL-EN16931-R001'.
     * Must be exactly 19 characters so it fits the existing errors[] 'rule' slot.
     */
    public function id(): string;

    /**
     * Evaluate the rule against the document.
     *
     * @param DOMXPath         $xpath   Registered-namespace XPath evaluator for the document.
     * @param ValidationContext $context Extracted document-level fields (type, currency, countries).
     * @return array<int, ValidationViolation> Empty when the rule passes; one or more violations otherwise.
     */
    public function validate(DOMXPath $xpath, ValidationContext $context): array;
}
