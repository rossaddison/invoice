<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 abs(value) — absolute value.
 *
 * Used 16 times in the Schematron for tax amount tolerance checks.
 * Not available in XPath 1.0 / PHP DOMXPath; must be evaluated natively.
 */
readonly class Abs implements Expression
{
    public function __construct(public Expression $value) {}
}
