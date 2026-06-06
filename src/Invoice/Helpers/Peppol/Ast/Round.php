<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 1.0 round(value) — rounds to the nearest integer.
 *
 * The Schematron uses this in monetary tolerance checks:
 *   round(xs:decimal(cbc:TaxAmount) * 10 * 10) div 100
 */
readonly class Round implements Expression
{
    public function __construct(public Expression $value) {}
}
