<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/** XPath 2.0 exists(path) — true when the path matches at least one node. */
readonly class Exists implements Expression
{
    public function __construct(public Expression $path) {}
}
