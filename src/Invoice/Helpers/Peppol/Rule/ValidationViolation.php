<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Rule;

/**
 * An immutable record of a single rule violation found during validation.
 *
 * What?  One violation produced by a ValidationRule — carries the severity, the rule ID
 *        (e.g. 'PEPPOL-EN16931-R001'), the human-readable message, and the location in
 *        the source XML (line number and XPath).
 * Why?   A typed value object is easier to test, route, and format than a raw array.
 *        It also carries Severity so callers can separate fatal errors from warnings
 *        without string-matching on the message.
 * When?  Instantiated by AbstractRule::fatal() / warn() / info() inside each rule's
 *        validate() method and returned in the array that RuleRegistry collects.
 * Where? Consumed by PeppolValidator::validateWithRegistry(), which fans violations out
 *        into $errors (Fatal) and $warnings (Warning / Info) for backward compatibility.
 * How?   Plain readonly constructor — no behaviour, no mutation.
 */
final class ValidationViolation
{
    public function __construct(
        public readonly Severity     $severity,
        public readonly string       $ruleId,
        public readonly string       $message,
        public readonly string|null  $line,
        public readonly string|null  $xpath,
    ) {}
}
