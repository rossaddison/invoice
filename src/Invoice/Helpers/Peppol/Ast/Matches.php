<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 matches(value, pattern) — true when value matches the regex pattern.
 *
 * Used 15 times for format checks (numeric-only IDs, postal codes, etc.).
 * PHP evaluation: preg_match() after converting the XPath 2.0 regex dialect
 * to PCRE (they are mostly compatible for the patterns used in Peppol).
 */
readonly class Matches implements Expression
{
    public function __construct(
        public Expression $value,
        public Expression $pattern,
    ) {}
}
