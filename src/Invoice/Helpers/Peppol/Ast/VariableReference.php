<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A reference to a variable bound by a QuantifiedExpression.
 *
 * Replaces VariableRef.  The name VariableReference is the full term used
 * in the XPath 2.0 specification.
 *
 * $name is the variable name WITHOUT the leading $, e.g. 'x' not '$x'.
 *
 * Used inside the satisfies sub-expression of QuantifiedExpression:
 *   some $x in tokenize('380 381', '\s') satisfies normalize-space(.) = $x
 *   → QuantifiedExpression(SOME, 'x',
 *         Literal('380 381'),
 *         BinaryExpression(EQ,
 *             FunctionCall('normalize-space', [PathExpression('.')]),
 *             VariableReference('x')))
 */

/**
 * @psalm-suppress UnusedClass
 */
readonly class VariableReference implements Expression
{
    public function __construct(public string $name) {}
}
