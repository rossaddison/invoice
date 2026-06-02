<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 upper-case(value) — converts a string to uppercase.
 *
 * Used 175 times in the Schematron — every country code, currency code, and
 * identifier comparison goes through this to be case-insensitive.
 * PHP evaluation: strtoupper() on the resolved string value.
 */
readonly class UpperCase implements Expression
{
    public function __construct(public Expression $value) {}
}
