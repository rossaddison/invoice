<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A binary expression: left operator right.
 *
 * Replaces thirteen individual comparison and arithmetic node classes
 * (Equal, NotEqual, GreaterThan, LessThan, GreaterThanOrEqual, LessThanOrEqual,
 * AndNode, OrNode, Add, Subtract, Multiply, Divide, Modulo) with a single
 * parameterised node whose behaviour is determined by BinaryOperator.
 *
 * Examples:
 *   xs:decimal(cbc:TaxAmount) > 0
 *     → BinaryExpression(GT, Decimal(Path('cbc:TaxAmount')), Literal(0))
 *
 *   cbc:ChargeIndicator = 'true'
 *     → BinaryExpression(EQ, Path('cbc:ChargeIndicator'), Literal('true'))
 *
 *   TaxExclusiveAmount + TaxAmount = TaxInclusiveAmount
 *     → BinaryExpression(EQ,
 *           BinaryExpression(ADD, Path('cbc:TaxExclusiveAmount'), Path('cbc:TaxAmount')),
 *           Path('cbc:TaxInclusiveAmount'))
 */
readonly class BinaryExpression implements Expression
{
    public function __construct(
        public BinaryOperator $operator,
        public Expression     $left,
        public Expression     $right,
    ) {}
}
