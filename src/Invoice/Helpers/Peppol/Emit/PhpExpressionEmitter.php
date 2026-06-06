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
 * Traverses the Expression AST and emits a PHP expression string.
 *
 * Each emitted string is valid PHP code that evaluates the same predicate
 * as the original XPath 2.0 expression, but against typed VO properties
 * instead of a DOM tree.
 *
 * $contextVar — the PHP variable name holding the current VO (e.g. '$v').
 * Inside Some/Every loops the contextVar shifts to the loop variable.
 */
final class PhpExpressionEmitter
{
    public function __construct(
        private readonly VoPathMapper $paths,
    ) {}

    public function emit(Expression $expr, string $contextVar = '$v'): string // NOSONAR php:S3776
    {
        return match (true) {
            $expr instanceof Literal          => $this->emitLiteral($expr),
            $expr instanceof Path             => $this->paths->map($expr->xpath, $contextVar),
            $expr instanceof VariableRef      => '$' . $expr->name,
            $expr instanceof BinaryExpression => $this->emitBinary($expr, $contextVar),
            $expr instanceof Not              => $expr->operand instanceof Exists
                                                    ? 'empty(' . $this->emit($expr->operand->path, $contextVar) . ')'
                                                    : '!(' . $this->emit($expr->operand, $contextVar) . ')',
            $expr instanceof Exists           => '!empty(' . $this->emit($expr->path, $contextVar) . ')',
            $expr instanceof NotExists        => 'empty(' . $this->emit($expr->path, $contextVar) . ')',
            $expr instanceof Count            => 'count(' . $this->emit($expr->path, $contextVar) . ')',
            $expr instanceof Sum              => 'array_sum(' . $this->emit($expr->path, $contextVar) . ')',
            $expr instanceof Round            => 'round(' . $this->emit($expr->value, $contextVar) . ')',
            $expr instanceof Abs              => 'abs(' . $this->emit($expr->value, $contextVar) . ')',
            $expr instanceof Decimal          => '(float)(' . $this->emit($expr->value, $contextVar) . ')',
            $expr instanceof UpperCase        => 'strtoupper((string)(' . $this->emit($expr->value, $contextVar) . '))',
            $expr instanceof StringCast       => '(string)(' . $this->emit($expr->value, $contextVar) . ')',
            $expr instanceof Contains         => 'str_contains(' . $this->emit($expr->haystack, $contextVar) . ', ' . $this->emit($expr->needle, $contextVar) . ')',
            $expr instanceof StartsWith       => 'str_starts_with(' . $this->emit($expr->string, $contextVar) . ', ' . $this->emit($expr->prefix, $contextVar) . ')',
            $expr instanceof Matches          => $this->emitMatches($expr, $contextVar),
            $expr instanceof IfThenElse       => $this->emitIfThenElse($expr, $contextVar),
            $expr instanceof Some             => $this->emitQuantified($expr->variable, $expr->in, $expr->satisfies, 'some', $contextVar),
            $expr instanceof Every            => $this->emitQuantified($expr->variable, $expr->in, $expr->satisfies, 'every', $contextVar),
            $expr instanceof InCodeList       => $this->emitInCodeList($expr, $contextVar),
            $expr instanceof Checksum         => $this->emitChecksum($expr, $contextVar),
            $expr instanceof FunctionCall     => $this->emitFunctionCall($expr, $contextVar),
            $expr instanceof Union            => 'array_merge(' . $this->emit($expr->left, $contextVar) . ', ' . $this->emit($expr->right, $contextVar) . ')',
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
            default                => "'" . addslashes($expr->value) . "'",
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
            BinaryOperator::MOD => "fmod({$l}, {$r})",
        };
    }

    // ── Quantified (some / every) ─────────────────────────────────────────────

    private function emitQuantified(
        string     $variable,
        Expression $in,
        Expression $satisfies,
        string     $kind,
        string     $contextVar
    ): string {
        $loopVar   = '$' . $variable;
        $inExpr    = $this->emit($in, $contextVar);
        $bodyExpr  = $this->emit($satisfies, $loopVar);
        $useClause = "use ({$contextVar})";

        if ($kind === 'some') {
            return "(static function() {$useClause}: bool {\n"
                 . "    foreach ({$inExpr} as {$loopVar}) {\n"
                 . "        if ({$bodyExpr}) { return true; }\n"
                 . "    }\n"
                 . "    return false;\n"
                 . "})()";
        }

        return "(static function() {$useClause}: bool {\n"
             . "    foreach ({$inExpr} as {$loopVar}) {\n"
             . "        if (!({$bodyExpr})) { return false; }\n"
             . "    }\n"
             . "    return true;\n"
             . "})()";
    }

    // ── Other expressions ────────────────────────────────────────────────────

    private function emitMatches(Matches $expr, string $ctx): string
    {
        $value   = $this->emit($expr->value, $ctx);
        $pattern = $this->emit($expr->pattern, $ctx);
        return "(bool) preg_match('/' . str_replace('/', '\\/', {$pattern}) . '/', {$value})";
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
        return "in_array({$value}, \\App\\Invoice\\Helpers\\Peppol\\CodeList::load(\\App\\Invoice\\Helpers\\Peppol\\CodeLists::{$listName}), true)";
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
            'normalize-space'  => 'trim((string)(' . ($args[0] ?? $ctx) . '))',
            'string-length'    => 'strlen(trim((string)(' . ($args[0] ?? $ctx) . ')))',
            'lower-case'       => 'strtolower((string)(' . ($args[0] ?? $ctx) . '))',
            'ends-with'        => 'str_ends_with(' . $args[0] . ', ' . $args[1] . ')',
            'concat'           => 'implode(\'\', [' . implode(', ', $args) . '])',
            'substring-before' => '(explode(' . $args[1] . ', ' . $args[0] . ')[0] ?? \'\')',
            'substring-after'  => '(explode(' . $args[1] . ', ' . $args[0] . ', 2)[1] ?? \'\')',
            'tokenize'         => 'preg_split(\'/' . addslashes($args[1] ?? '\\\\s') . '/\', (string)(' . ($args[0] ?? '\'\'') . '))',
            'string-join'      => 'implode(' . ($args[1] ?? '\'\'') . ', ' . ($args[0] ?? '[]') . ')',
            'true'             => 'true',
            'false'            => 'false',
            'number'           => '(float)(' . ($args[0] ?? $ctx) . ')',
            'boolean'          => '(bool)(' . ($args[0] ?? $ctx) . ')',
            default            => '/* TODO: ' . $expr->name . '(' . implode(', ', $args) . ') */',
        };
    }
}
