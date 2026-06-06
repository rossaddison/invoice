<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A Schematron <rule> — a context pattern plus its assertions.
 *
 * What?  A group of assertions that all share the same XPath context.
 *        The context locates the nodes to validate; each Assertion then
 *        runs its test relative to each matched context node.
 * Why?   Mirrors the Schematron model directly so a parser can produce
 *        Rule objects that an evaluator can walk without losing the
 *        grouping information from the original .sch file.
 * When?  Produced by the Schematron parser; consumed by a rule runner
 *        that queries the document for context nodes then evaluates
 *        each Assertion against every matched node.
 * Where? Top-level structure — a Schematron pattern is a collection of Rules.
 * How?   The runner evaluates context via DOMXPath::query($rule->context),
 *        then for each returned node calls
 *        ExpressionEvaluator::evaluate($assertion->test, $xpath, $contextNode).
 */

/**
 * @psalm-suppress UnusedClass
 */
readonly class Rule
{
    /**
     * @param Assertion[] $assertions
     */
    public function __construct(
        public string $context,
        public array  $assertions,
    ) {}
}
