<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath | (union) operator — merges two node sets.
 *
 * Used 140 times in the Schematron to handle both Invoice and CreditNote
 * in a single expression:
 *   (cac:InvoiceLine | cac:CreditNoteLine) / cbc:LineExtensionAmount
 *
 * Chained unions (A | B | C) parse as Union(Union(A, B), C).
 */
readonly class Union implements Expression
{
    public function __construct(
        public Expression $left,
        public Expression $right,
    ) {}
}
