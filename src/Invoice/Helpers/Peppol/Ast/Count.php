<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/** XPath 1.0 count(path) — number of nodes matched by path. */
readonly class Count implements Expression
{
    public function __construct(public Expression $path) {}
}
