<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/** XPath 1.0 starts-with(string, prefix) — true when string begins with prefix. */
readonly class StartsWith implements Expression
{
    public function __construct(
        public Expression $string,
        public Expression $prefix,
    ) {}
}
