<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath string-length(value?) — returns the number of characters in a string.
 *
 * Called with no argument it measures the string-value of the context node.
 * The parser supplies Path('.') as the default argument in that case.
 *
 * Used in Peppol assertions to enforce maximum field lengths,
 * e.g. string-length(cbc:ID) <= 200.
 */
readonly class StringLength implements Expression
{
    public function __construct(public Expression $value) {}
}
