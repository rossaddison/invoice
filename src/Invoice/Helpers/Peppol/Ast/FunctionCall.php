<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A named function call with zero or more arguments.
 *
 * Replaces the standalone Exists, Count, Sum, Round, Abs, Decimal, StringCast,
 * UpperCase, Contains, StartsWith and Matches node classes.  The function name
 * carries its namespace prefix when applicable (e.g. 'xs:decimal', 'u:gln').
 *
 * Standard XPath 1.0/2.0 functions handled by ExpressionEvaluator:
 *   normalize-space, string, upper-case, lower-case, string-length, boolean,
 *   not, number, xs:decimal, xs:integer, true, false, exists, count, sum,
 *   round, abs, floor, ceiling, contains, starts-with, ends-with, concat,
 *   matches, replace, tokenize, string-join, substring-before, substring-after.
 *
 * Unknown names fall through to the injected checksum handler registry
 * (u:gln, u:mod11, u:mod97-0208, etc.).
 *
 * Examples:
 *   normalize-space(cbc:ID)
 *     → FunctionCall('normalize-space', [PathExpression('cbc:ID')])
 *
 *   xs:decimal(cbc:TaxAmount)
 *     → FunctionCall('xs:decimal', [PathExpression('cbc:TaxAmount')])
 *
 *   matches(normalize-space(cbc:CompanyID), '^\d{9}$')
 *     → FunctionCall('matches', [
 *           FunctionCall('normalize-space', [PathExpression('cbc:CompanyID')]),
 *           Literal('^\d{9}$')
 *       ])
 */

/**
 * @psalm-suppress UnusedClass
 */
readonly class FunctionCall implements Expression
{
    /**
     * @param string       $name      Function name including namespace prefix where applicable.
     * @param Expression[] $arguments Positional arguments in declaration order.
     */
    public function __construct(
        public string $name,
        public array  $arguments = [],
    ) {}
}
