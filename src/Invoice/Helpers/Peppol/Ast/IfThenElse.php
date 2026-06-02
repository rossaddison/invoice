<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 conditional: if (condition) then thenExpr else elseExpr.
 *
 * Used 4 times in the Schematron, e.g. inside the IBAN mod-97 calculation
 * and in national CIUS profile checks.
 */
readonly class IfThenElse implements Expression
{
    public function __construct(
        public Expression $condition,
        public Expression $then,
        public Expression $else,
    ) {}
}
