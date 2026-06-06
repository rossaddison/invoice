<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 quantified expression: every $variable in $in satisfies $satisfies.
 *
 * True when every item in the sequence satisfies the predicate.
 * Used 6 times in the Schematron for universal constraints across node sets.
 *
 * $variable is the variable name without the leading $, e.g. 'n'.
 */
readonly class Every implements Expression
{
    public function __construct(
        public string     $variable,
        public Expression $in,
        public Expression $satisfies,
    ) {}
}
