<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath sum(path) — numeric sum of the string-values of all matched nodes.
 *
 * In the Schematron this always wraps an xs:decimal() cast, e.g.:
 *   sum(//(cac:InvoiceLine|cac:CreditNoteLine)/xs:decimal(cbc:LineExtensionAmount))
 * so $path will often be a Decimal node.
 */
readonly class Sum implements Expression
{
    public function __construct(public Expression $path) {}
}
