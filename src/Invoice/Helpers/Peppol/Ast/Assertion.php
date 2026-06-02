<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A single <assert> or <report> element from a Schematron rule.
 *
 * What?  The atomic validation unit — one XPath test expression, one rule ID,
 *        one human-readable message.
 * Why?   Decouples the test predicate (an Expression AST) from the prose message
 *        and the Peppol rule identifier, so the evaluator, the reporter, and a
 *        future rule-linter can each consume only what they need.
 * When?  Assembled by the Schematron parser when it reads a <rule> element;
 *        evaluated by ExpressionEvaluator against a loaded DOMDocument.
 * Where? Held in Rule::$assertions; evaluated inside a ValidationRule that
 *        delegates to ExpressionEvaluator.
 * How?   $test is the compiled AST for the XPath test attribute;
 *        ExpressionEvaluator::evaluate($test, …) returns bool — false means
 *        the assertion fired (i.e. the document is invalid for this rule).
 */
readonly class Assertion
{
    public function __construct(
        public Expression $test,
        public string     $message,
        public string     $id,
        /** 'fatal' or 'warning' — maps to Severity::Fatal / Severity::Warning at evaluation time. */
        public string     $flag = 'fatal',
    ) {}
}
