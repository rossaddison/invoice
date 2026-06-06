<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * Every binary operator recognised in Peppol / EN16931 XPath 2.0 expressions.
 *
 * The string value is the literal operator token that appears in the Schematron
 * source, making round-trip pretty-printing straightforward.
 *
 * Logical operators (AND / OR) are included here so that BinaryExpression covers
 * all two-operand constructs uniformly.  The evaluator short-circuits AND and OR
 * using PHP's native && / || operators, matching XPath 2.0 semantics.
 *
 * Note: AND and OR are reserved PHP keywords but are legal as enum case names
 * when accessed via the :: operator (PHP 7.1+).
 */
enum BinaryOperator: string
{
    // ── Comparison ────────────────────────────────────────────────────────────
    case EQ  = '=';
    case NE  = '!=';
    case GT  = '>';
    case LT  = '<';
    case GTE = '>=';
    case LTE = '<=';

    // ── Logical ───────────────────────────────────────────────────────────────
    case AND = 'and';
    case OR  = 'or';

    // ── Arithmetic ────────────────────────────────────────────────────────────
    case ADD = '+';
    case SUB = '-';
    case MUL = '*';
    case DIV = 'div';   // XPath uses 'div', not '/', for numeric division
    case MOD = 'mod';
}
