<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Ast\ExpressionEvaluator;
use App\Invoice\Helpers\Peppol\Ast\Rule;
use App\Invoice\Helpers\Peppol\Rule\Severity;
use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;
use DOMDocument;
use DOMNode;
use DOMXPath;

/**
 * Executes a parsed SchematronDocument against a loaded UBL invoice DOMDocument.
 *
 * Pipeline:
 *   1. Register namespace prefixes from SchematronDocument::$namespaces with DOMXPath.
 *   2. Evaluate schema-level variables (SchematronDocument::$variables) against the
 *      document root to build the initial $bindings map.
 *   3. For each Rule, query the document for all context nodes.
 *   4. For each context node, evaluate every Assertion test via ExpressionEvaluator.
 *      A test evaluating to FALSE is a violation (Schematron <assert> convention).
 *   5. Collect and return ValidationViolation objects.
 *
 * Usage:
 *   $doc     = (new SchematronParser())->parseFile('/path/to/peppol-bis.sch');
 *   $runner  = new SchematronRuleRunner(new ExpressionEvaluator($checksumHandlers));
 *   $issues  = $runner->run($doc, $invoiceDom);
 */
/** @psalm-suppress UnusedClass */
final class SchematronRuleRunner
{
    public function __construct(
        private readonly ExpressionEvaluator $evaluator,
    ) {}

    /**
     * Validate $document against all rules in $doc and return every violation found.
     *
     * @return ValidationViolation[]
     */
    public function run(SchematronDocument $doc, DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);
        $this->registerNamespaces($xpath, $doc->namespaces);
        $bindings   = $this->buildBindings($doc, $xpath);
        $violations = [];

        foreach ($doc->rules as $rule) {
            array_push($violations, ...$this->runRule($rule, $xpath, $bindings));
        }

        return $violations;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * @param array<string, string> $namespaces
     */
    private function registerNamespaces(DOMXPath $xpath, array $namespaces): void
    {
        foreach ($namespaces as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }
    }

    /**
     * Evaluate schema-level <sch:variable> select expressions against the document root.
     *
     * @return array<string, mixed>
     */
    private function buildBindings(SchematronDocument $doc, DOMXPath $xpath): array
    {
        $bindings = [];
        foreach ($doc->variables as $name => $selectExpr) {
            /** @psalm-suppress MixedAssignment */
            $bindings[$name] = $this->evaluator->evaluate($selectExpr, $xpath);
        }
        return $bindings;
    }

    /**
     * Run all assertions in one rule and return any violations.
     *
     * @param array<string, mixed> $bindings
     * @return ValidationViolation[]
     */
    private function runRule(Rule $rule, DOMXPath $xpath, array $bindings): array
    {
        $violations   = [];
        $contextNodes = $xpath->query($rule->context);

        if ($contextNodes === false) {
            return $violations;
        }

        foreach ($contextNodes as $contextNode) {
            if (!$contextNode instanceof \DOMNode) {
                continue; // DOMNameSpaceNode can appear in DOMNodeList; skip it
            }
            foreach ($rule->assertions as $assertion) {
                if (!$this->evaluator->evaluateBool($assertion->test, $xpath, $contextNode, $bindings)) {
                    $violations[] = new ValidationViolation(
                        severity: $this->mapFlag($assertion->flag),
                        ruleId:   $assertion->id,
                        message:  $assertion->message,
                        line:     (string) $contextNode->getLineNo(),
                        xpath:    XPathHelper::buildPath($contextNode),
                    );
                }
            }
        }

        return $violations;
    }

    private function mapFlag(string $flag): Severity
    {
        return $flag === 'warning' ? Severity::Warning : Severity::Fatal;
    }
}
