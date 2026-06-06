<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Rule;

use App\Invoice\Helpers\Peppol\XPathHelper;
use DOMNode;
use DOMXPath;

/**
 * Convenience base class for concrete rule implementations.
 *
 * Provides queryValue(), queryNode(), and three factory methods — fatal(), warn(),
 * info() — so rule classes stay focused on their validation logic rather than
 * repeating DOM boilerplate.
 */
abstract class AbstractRule implements ValidationRule
{
    /**
     * Return the trimmed text content of the first node matched by $expr, or null.
     */
    protected function queryValue(
        DOMXPath $xpath,
        string $expr,
        ?DOMNode $context = null
    ): ?string {
        $nodes = $context !== null
            ? $xpath->query($expr, $context)
            : $xpath->query($expr);

        $first = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;
        $value = $first?->nodeValue;
        return $value !== null ? trim($value) : null;
    }

    /**
     * Return the first node matched by $expr, or null.
     */
    protected function queryNode(
        DOMXPath $xpath,
        string $expr,
        ?DOMNode $context = null
    ): ?DOMNode {
        $nodes = $context !== null
            ? $xpath->query($expr, $context)
            : $xpath->query($expr);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $node = $nodes->item(0);
        return ($node instanceof DOMNode) ? $node : null;
    }

    /** Build a Fatal violation for this rule. */
    protected function fatal(string $message, ?DOMNode $node = null): ValidationViolation
    {
        return $this->violation(Severity::Fatal, $message, $node);
    }

    /** Build a Warning violation for this rule. */
    protected function warn(string $message, ?DOMNode $node = null): ValidationViolation
    {
        return $this->violation(Severity::Warning, $message, $node);
    }

    /** Build an Info violation for this rule. */
    protected function info(string $message, ?DOMNode $node = null): ValidationViolation
    {
        return $this->violation(Severity::Info, $message, $node);
    }

    private function violation(
        Severity $severity,
        string $message,
        ?DOMNode $node
    ): ValidationViolation {
        return new ValidationViolation(
            severity: $severity,
            ruleId:   $this->id(),
            message:  $message,
            line:     $node !== null ? (string) $node->getLineNo() : null,
            xpath:    $node !== null ? XPathHelper::buildPath($node) : null,
        );
    }
}
