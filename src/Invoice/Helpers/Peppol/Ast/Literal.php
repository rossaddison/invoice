<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A scalar literal value — a string, integer, float, or boolean constant.
 *
 * Examples from Schematron test expressions:
 *   'GBP'   → Literal('GBP')
 *   0       → Literal(0)
 *   100.0   → Literal(100.0)
 *   true()  → Literal(true)    (after resolving the XPath true() function)
 */
readonly class Literal implements Expression
{
    public function __construct(public string|int|float|bool $value) {}
}
