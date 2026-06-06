<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 1.0 string(value) — converts a value to its string representation.
 *
 * Named StringCast rather than String because `string` is a reserved PHP
 * keyword and cannot be used as a class name (class names are case-insensitive
 * in PHP, so String === string to the parser).
 */
readonly class StringCast implements Expression
{
    public function __construct(public Expression $value) {}
}
