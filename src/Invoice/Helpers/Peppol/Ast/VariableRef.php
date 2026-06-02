<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A reference to a loop variable bound by Some or Every.
 *
 * When Some or Every evaluates its satisfies sub-expression it extends the
 * $bindings array with the current item keyed by the variable name.  This
 * node looks that binding up at evaluation time.
 *
 * Example — Schematron:
 *   some $x in tokenize('380 381', '\s') satisfies normalize-space(.) = $x
 *
 * AST:
 *   Some('x',
 *       Literal('380 381'),           // evalSequence splits on whitespace
 *       Equal(Path('.'), VariableRef('x'))
 *   )
 *
 * $name is the variable name WITHOUT the leading $, e.g. 'x' not '$x'.
 * This replaces the Path('$x') workaround that was noted in ExpressionEvaluator.
 */
readonly class VariableRef implements Expression
{
    public function __construct(public string $name) {}
}
