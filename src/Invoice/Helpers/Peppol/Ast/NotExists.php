<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/** Logical inverse of exists() — true when the path matches no nodes. */
readonly class NotExists implements Expression
{
    public function __construct(public Expression $path) {}
}
