<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A single-operand expression: operator operand.
 *
 * Replaces the standalone Not and NotExists node classes.
 *
 * Examples:
 *   not(exists(cbc:ID))
 *     → UnaryExpression(NOT, FunctionCall('exists', [PathExpression('cbc:ID')]))
 *
 *   -xs:decimal(cbc:TaxAmount)
 *     → UnaryExpression(MINUS, FunctionCall('xs:decimal', [PathExpression('cbc:TaxAmount')]))
 */

/**
 * @psalm-suppress UnusedClass
 */
readonly class UnaryExpression implements Expression
{
    public function __construct(
        public UnaryOperator $operator,
        public Expression    $operand,
    ) {}
}
