<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/** XPath not(expr) — boolean negation. */
readonly class Not implements Expression
{
    public function __construct(public Expression $operand) {}
}
