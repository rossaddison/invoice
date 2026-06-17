<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Calculator;

use App\Invoice\Helpers\Peppol\XPathHelper;
use DOMElement;
use DOMNode;
use DOMXPath;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Base class for Peppol invoice calculators.
 *
 * Provides shared XPath helpers, error accumulation, and the addError()
 * format expected by PeppolValidator (rule/text split at position 19/20).
 */
abstract class AbstractCalculator
{
    /**
     * What?  Errors collected during validate(), in the same shape as PeppolValidator::$errors.
     * Why?   PeppolValidator merges these into its own error list after calling each calculator.
     * When?  Written by addError() during validate(); read by PeppolValidator after validate() returns.
     * Where? Returned by getErrors(); merged into PeppolValidator via array_push(...$calc->getErrors()).
     * How?   Each entry carries rule (first 19 chars), text (from char 20), line, and xpath.
     *
     * @var array<int, array{rule: string, text: string, line: string|null, xpath: string|null}>
     */
    protected array $errors = [];

    /** @var array<int, array{message: string, line: int|null, xpath: string|null}> */
    protected array $warnings = [];

    /**
     * What?  Maximum absolute difference that is still treated as equal when comparing two amounts.
     * Why?   Monetary values in UBL are rounded to 2 decimal places; floating-point arithmetic
     *        can produce tiny residuals that should not trigger a validation error.
     * When?  Referenced in every abs($a - $b) > self::TOLERANCE comparison.
     * Where? Used across all subclass validate methods.
     * How?   0.01 covers one full cent of rounding error, matching EN16931 guidance.
     */
    protected const float TOLERANCE = 0.01;

    public function __construct(
        protected readonly DOMXPath $xpath,
        protected readonly TranslatorInterface $t
    ) {}

    /**
     * Run all validation rules for this calculator.
     * Errors are accumulated in $this->errors and retrieved via getErrors().
     */
    abstract public function validate(): void;

    /**
     * @return array<int, array{rule: string, text: string, line: string|null, xpath: string|null}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<int, array{message: string, line: int|null, xpath: string|null}>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    protected function addWarning(string $message, ?DOMNode $node = null): void
    {
        $this->warnings[] = [
            'message' => $message,
            'line'    => $node !== null ? $node->getLineNo() : null,
            'xpath'   => $node !== null ? $this->buildXPath($node) : null,
        ];
    }

    /**
     * Sum the nodeValue of every node matched by $xpathExpr.
     */
    protected function sumXPath(string $xpathExpr): float
    {
        $nodes = $this->xpath->query($xpathExpr);
        if ($nodes === false) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($nodes as $node) {
            $val = $node->nodeValue;
            if ($val !== null) {
                $sum += (float) trim($val);
            }
        }
        return $sum;
    }

    protected function getNodeValue(
        string $xpathExpr,
        ?DOMNode $contextNode = null
    ): ?string {
        $nodes = $contextNode !== null
            ? $this->xpath->query($xpathExpr, $contextNode)
            : $this->xpath->query($xpathExpr);

        $first = ($nodes !== false && $nodes->length > 0) ? $nodes->item(0) : null;
        $value = $first?->nodeValue;
        return $value !== null ? trim($value) : null;
    }

    protected function getNode(
        string $xpathExpr,
        ?DOMNode $contextNode = null
    ): ?DOMNode {
        $nodes = $contextNode !== null
            ? $this->xpath->query($xpathExpr, $contextNode)
            : $this->xpath->query($xpathExpr);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $node = $nodes->item(0);
        return ($node instanceof DOMNode) ? $node : null;
    }

    /**
     * Record a validation error with the same rule/text split used by PeppolValidator.
     * The $message must be formatted as: "<19-char rule id>: <translation text>".
     */
    protected function addError(
        string $message,
        ?DOMNode $node = null
    ): void {
        $lineNo        = null;
        $computedXPath = null;

        if ($node !== null) {
            $lineNo        = (string) $node->getLineNo();
            $computedXPath = $this->buildXPath($node);
        }

        $this->errors[] = [
            'rule'  => substr($message, 0, 19),
            'text'  => substr($message, 20),
            'line'  => $lineNo,
            'xpath' => $computedXPath,
        ];
    }

    private function buildXPath(DOMNode $node): string
    {
        return XPathHelper::buildPath($node);
    }
}
