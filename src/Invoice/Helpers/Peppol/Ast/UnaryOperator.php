<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * Single-operand operators.
 *
 * NOT  — logical negation; in XPath written as the not() function but modelled
 *        here as a first-class operator so the evaluator branch is symmetric
 *        with BinaryOperator rather than mixed in with arbitrary FunctionCalls.
 * MINUS — arithmetic negation; written as a unary - prefix in XPath expressions
 *         such as -xs:decimal(cbc:TaxAmount).
 */
enum UnaryOperator: string
{
    case NOT   = 'not';
    case MINUS = '-';
}
