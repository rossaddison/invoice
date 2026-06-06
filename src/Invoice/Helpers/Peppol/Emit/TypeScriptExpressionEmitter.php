<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit;

use App\Invoice\Helpers\Peppol\Ast\Abs;
use App\Invoice\Helpers\Peppol\Ast\BinaryExpression;
use App\Invoice\Helpers\Peppol\Ast\BinaryOperator;
use App\Invoice\Helpers\Peppol\Ast\Checksum;
use App\Invoice\Helpers\Peppol\Ast\Contains;
use App\Invoice\Helpers\Peppol\Ast\Count;
use App\Invoice\Helpers\Peppol\Ast\Decimal;
use App\Invoice\Helpers\Peppol\Ast\Every;
use App\Invoice\Helpers\Peppol\Ast\Exists;
use App\Invoice\Helpers\Peppol\Ast\Expression;
use App\Invoice\Helpers\Peppol\Ast\FunctionCall;
use App\Invoice\Helpers\Peppol\Ast\IfThenElse;
use App\Invoice\Helpers\Peppol\Ast\InCodeList;
use App\Invoice\Helpers\Peppol\Ast\Literal;
use App\Invoice\Helpers\Peppol\Ast\Matches;
use App\Invoice\Helpers\Peppol\Ast\Not;
use App\Invoice\Helpers\Peppol\Ast\NotExists;
use App\Invoice\Helpers\Peppol\Ast\Path;
use App\Invoice\Helpers\Peppol\Ast\Round;
use App\Invoice\Helpers\Peppol\Ast\Some;
use App\Invoice\Helpers\Peppol\Ast\StartsWith;
use App\Invoice\Helpers\Peppol\Ast\StringCast;
use App\Invoice\Helpers\Peppol\Ast\Sum;
use App\Invoice\Helpers\Peppol\Ast\Union;
use App\Invoice\Helpers\Peppol\Ast\UpperCase;
use App\Invoice\Helpers\Peppol\Ast\VariableRef;

/**
 * Traverses the Expression AST and emits a TypeScript expression string.
 *
 * Each emitted string is valid TypeScript code that evaluates the same predicate
 * as the original XPath 2.0 expression, but against typed VO properties
 * instead of a DOM tree.
 *
 * $contextVar — the TypeScript variable name holding the current VO (e.g. 'v').
 * Inside Some/Every loops the contextVar shifts to the loop variable.
 */

/** @psalm-suppress UnusedClass */
final class TypeScriptExpressionEmitter
{
    public function __construct(
        private readonly TypeScriptVoPathMapper $paths,
    ) {}

    public function emit(Expression $expr, string $contextVar = 'v'): string // NOSONAR php:S3776
    {
        return match (true) {
            $expr instanceof Literal          => $this->emitLiteral($expr),
            $expr instanceof Path             => $this->paths->map($expr->xpath, $contextVar),
            $expr instanceof VariableRef      => $expr->name,
            $expr instanceof BinaryExpression => $this->emitBinary($expr, $contextVar),
            $expr instanceof Not              => $expr->operand instanceof Exists
                                                    ? '!(' . $this->emitExists($expr->operand, $contextVar) . ')'
                                                    : '!(' . $this->emit($expr->operand, $contextVar) . ')',
            $expr instanceof Exists           => $this->emitExists($expr, $contextVar),
            $expr instanceof NotExists        => $this->emitNotExists($expr, $contextVar),
            $expr instanceof Count            => '(' . $this->emit($expr->path, $contextVar) . ').length',
            $expr instanceof Sum              => $this->emitSum($expr, $contextVar),
            $expr instanceof Round            => 'Math.round(' . $this->emit($expr->value, $contextVar) . ')',
            $expr instanceof Abs              => 'Math.abs(' . $this->emit($expr->value, $contextVar) . ')',
            $expr instanceof Decimal          => 'parseFloat(String(' . $this->emit($expr->value, $contextVar) . '))',
            $expr instanceof UpperCase        => 'String(' . $this->emit($expr->value, $contextVar) . ').toUpperCase()',
            $expr instanceof StringCast       => 'String(' . $this->emit($expr->value, $contextVar) . ')',
            $expr instanceof Contains         => 'String(' . $this->emit($expr->haystack, $contextVar) . ').includes(' . $this->emit($expr->needle, $contextVar) . ')',
            $expr instanceof StartsWith       => 'String(' . $this->emit($expr->string, $contextVar) . ').startsWith(' . $this->emit($expr->prefix, $contextVar) . ')',
            $expr instanceof Matches          => $this->emitMatches($expr, $contextVar),
            $expr instanceof IfThenElse       => $this->emitIfThenElse($expr, $contextVar),
            $expr instanceof Some             => $this->emitQuantified($expr->variable, $expr->in, $expr->satisfies, 'some', $contextVar),
            $expr instanceof Every            => $this->emitQuantified($expr->variable, $expr->in, $expr->satisfies, 'every', $contextVar),
            $expr instanceof InCodeList       => $this->emitInCodeList($expr, $contextVar),
            $expr instanceof Checksum         => $this->emitChecksum($expr, $contextVar),
            $expr instanceof FunctionCall     => $this->emitFunctionCall($expr, $contextVar),
            $expr instanceof Union            => '[...(' . $this->emit($expr->left, $contextVar) . ' ?? []), ...(' . $this->emit($expr->right, $contextVar) . ' ?? [])]',
            default                           => '/* TODO: ' . $expr::class . ' */',
        };
    }

    // ── Literal ───────────────────────────────────────────────────────────────

    private function emitLiteral(Literal $expr): string
    {
        return match (true) {
            is_bool($expr->value)  => $expr->value ? 'true' : 'false',
            is_int($expr->value)   => (string) $expr->value,
            is_float($expr->value) => rtrim(rtrim(number_format($expr->value, 10, '.', ''), '0'), '.'),
            default                => "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $expr->value) . "'",
        };
    }

    // ── Binary ────────────────────────────────────────────────────────────────

    private function emitBinary(BinaryExpression $expr, string $ctx): string
    {
        $l = $this->emit($expr->left,  $ctx);
        $r = $this->emit($expr->right, $ctx);

        return match ($expr->operator) {
            BinaryOperator::EQ  => "({$l} == {$r})",
            BinaryOperator::NE  => "({$l} != {$r})",
            BinaryOperator::GT  => "({$l} > {$r})",
            BinaryOperator::GTE => "({$l} >= {$r})",
            BinaryOperator::LT  => "({$l} < {$r})",
            BinaryOperator::LTE => "({$l} <= {$r})",
            BinaryOperator::AND => "({$l} && {$r})",
            BinaryOperator::OR  => "({$l} || {$r})",
            BinaryOperator::ADD => "({$l} + {$r})",
            BinaryOperator::SUB => "({$l} - {$r})",
            BinaryOperator::MUL => "({$l} * {$r})",
            BinaryOperator::DIV => "({$l} / {$r})",
            BinaryOperator::MOD => "({$l} % {$r})",
        };
    }

    // ── Exists / NotExists ────────────────────────────────────────────────────

    private function emitExists(Exists $expr, string $ctx): string
    {
        $pathExpr = $this->emit($expr->path, $ctx);
        if ($expr->path instanceof Path && $this->paths->isArrayPath($expr->path->xpath)) {
            return "({$pathExpr}).length > 0";
        }
        return "({$pathExpr}) != null && ({$pathExpr}) !== ''";
    }

    private function emitNotExists(NotExists $expr, string $ctx): string
    {
        $pathExpr = $this->emit($expr->path, $ctx);
        if ($expr->path instanceof Path && $this->paths->isArrayPath($expr->path->xpath)) {
            return "({$pathExpr}).length === 0";
        }
        return "({$pathExpr}) == null || ({$pathExpr}) === ''";
    }

    // ── Sum ───────────────────────────────────────────────────────────────────

    private function emitSum(Sum $expr, string $ctx): string
    {
        $pathExpr = $this->emit($expr->path, $ctx);
        return "({$pathExpr}).reduce((a: number, b: number) => a + b, 0)";
    }

    // ── Quantified (some / every) ─────────────────────────────────────────────

    private function emitQuantified(
        string     $variable,
        Expression $in,
        Expression $satisfies,
        string     $kind,
        string     $contextVar
    ): string {
        $inExpr   = $this->emit($in, $contextVar);
        $bodyExpr = $this->emit($satisfies, $variable);
        return "({$inExpr}).{$kind}(({$variable}) => {$bodyExpr})";
    }

    // ── Other expressions ────────────────────────────────────────────────────

    private function emitMatches(Matches $expr, string $ctx): string
    {
        $value   = $this->emit($expr->value, $ctx);
        $pattern = $this->emit($expr->pattern, $ctx);
        return "new RegExp({$pattern}).test(String({$value}))";
    }

    private function emitIfThenElse(IfThenElse $expr, string $ctx): string
    {
        return '(' . $this->emit($expr->condition, $ctx) . ' ? '
             . $this->emit($expr->then, $ctx) . ' : '
             . $this->emit($expr->else, $ctx) . ')';
    }

    private function emitInCodeList(InCodeList $expr, string $ctx): string
    {
        $value    = $this->emit($expr->value, $ctx);
        $listName = $expr->list->name;
        return "CodeList.load(CodeLists.{$listName}).includes({$value})";
    }

    private function emitChecksum(Checksum $expr, string $ctx): string
    {
        $fn    = $expr->algorithm->value;
        $value = $this->emit($expr->value, $ctx);
        return "/* checksum {$fn} */ true /* TODO: implement {$fn}({$value}) */";
    }

    private function emitFunctionCall(FunctionCall $expr, string $ctx): string
    {
        $args = array_map(fn(Expression $a) => $this->emit($a, $ctx), $expr->arguments);

        return match ($expr->name) {
            'normalize-space'  => 'String(' . ($args[0] ?? $ctx) . ').trim()',
            'string-length'    => 'String(' . ($args[0] ?? $ctx) . ').trim().length',
            'lower-case'       => 'String(' . ($args[0] ?? $ctx) . ').toLowerCase()',
            'ends-with'        => 'String(' . ($args[0] ?? '') . ').endsWith(' . ($args[1] ?? "''") . ')',
            'concat'           => '[' . implode(', ', $args) . "].join('')",
            'substring-before' => 'String(' . ($args[0] ?? '') . ').split(' . ($args[1] ?? "''") . ')[0]',
            'substring-after'  => "(String(" . ($args[0] ?? '') . ').split(' . ($args[1] ?? "''") . ", 2)[1] ?? '')",
            'tokenize'         => 'String(' . ($args[0] ?? "''") . ').split(new RegExp(' . ($args[1] ?? "'\\\\s'") . '))',
            'string-join'      => '(' . ($args[0] ?? '[]') . ').join(' . ($args[1] ?? "''") . ')',
            'true'             => 'true',
            'false'            => 'false',
            'number'           => 'parseFloat(String(' . ($args[0] ?? $ctx) . '))',
            'boolean'          => 'Boolean(' . ($args[0] ?? $ctx) . ')',
            default            => '/* TODO: ' . $expr->name . '(' . implode(', ', $args) . ') */',
        };
    }
}
