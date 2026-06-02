<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/** XPath 1.0 contains(haystack, needle) — true when haystack contains needle. */
readonly class Contains implements Expression
{
    public function __construct(
        public Expression $haystack,
        public Expression $needle,
    ) {}
}
