<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Ast\Abs;
use App\Invoice\Helpers\Peppol\Ast\BinaryExpression;
use App\Invoice\Helpers\Peppol\Ast\BinaryOperator;
use App\Invoice\Helpers\Peppol\Ast\CastableAs;
use App\Invoice\Helpers\Peppol\Ast\Checksum;
use App\Invoice\Helpers\Peppol\Ast\ChecksumAlgorithm;
use App\Invoice\Helpers\Peppol\Ast\Contains;
use App\Invoice\Helpers\Peppol\Ast\Count;
use App\Invoice\Helpers\Peppol\Ast\Decimal;
use App\Invoice\Helpers\Peppol\Ast\Every;
use App\Invoice\Helpers\Peppol\Ast\Exists;
use App\Invoice\Helpers\Peppol\Ast\ForExpression;
use App\Invoice\Helpers\Peppol\Ast\Expression;
use App\Invoice\Helpers\Peppol\Ast\FunctionCall;
use App\Invoice\Helpers\Peppol\Ast\IfThenElse;
use App\Invoice\Helpers\Peppol\Ast\Literal;
use App\Invoice\Helpers\Peppol\Ast\Matches;
use App\Invoice\Helpers\Peppol\Ast\Not;
use App\Invoice\Helpers\Peppol\Ast\NotExists;
use App\Invoice\Helpers\Peppol\Ast\Path;
use App\Invoice\Helpers\Peppol\Ast\Round;
use App\Invoice\Helpers\Peppol\Ast\Some;
use App\Invoice\Helpers\Peppol\Ast\StartsWith;
use App\Invoice\Helpers\Peppol\Ast\StringCast;
use App\Invoice\Helpers\Peppol\Ast\NormalizeSpace;
use App\Invoice\Helpers\Peppol\Ast\Sequence;
use App\Invoice\Helpers\Peppol\Ast\StringLength;
use App\Invoice\Helpers\Peppol\Ast\Substring;
use App\Invoice\Helpers\Peppol\Ast\Translate;
use App\Invoice\Helpers\Peppol\Ast\Sum;
use App\Invoice\Helpers\Peppol\Ast\UpperCase;
use App\Invoice\Helpers\Peppol\Ast\VariableRef;
use App\Invoice\Helpers\Peppol\Exception\XPathParseException;

/**
 * Parses XPath 2.0 expressions (Peppol BIS Billing 3.0 subset) into the Expression AST.
 *
 * Implements recursive descent over the XPath 2.0 constructs used in the PEPPOL BIS
 * Billing 3.0 Schematron.  Tokenisation is delegated to XPathTokenizer.  Anything
 * that is not handled by a specific AST node becomes a raw Path, delegated to DOMXPath.
 *
 * Operator precedence (low → high):
 *   or → and → comparison (= != < <= > >=) → additive (+ -)
 *   → multiplicative (* div mod) → unary (-) → primary
 *
 * Method budget: 19 explicit + 1 implicit constructor = 20 (SonarQube S1448 limit).
 */
final class XPathParser
{
    private ?XPathTokenizer $tokenizer = null;

    /** @var array<int, array{type: string, value: string}> */
    private array $tokens = [];
    private int $pos = 0;

    public function parse(string $expression): Expression
    {
        $this->tokenizer ??= new XPathTokenizer();
        $this->tokens = $this->tokenizer->tokenize($expression);
        $this->pos    = 0;
        $result       = $this->parseOr();
        if (!$this->tokenIs(XPathTokenizer::T_EOF)) {
            throw new XPathParseException(
                sprintf("Unparsed tokens starting at '%s' (pos %d) in: %s", $this->current()['value'], $this->pos, $expression)
            );
        }
        return $result;
    }

    // ── Token accessors ───────────────────────────────────────────────────────

    /** @return array{type: string, value: string} */
    private function current(): array
    {
        return $this->tokens[$this->pos] ?? ['type' => XPathTokenizer::T_EOF, 'value' => ''];
    }

    /**
     * True when the current token matches $type and, when $value is given, also matches $value.
     * Replaces the separate isType() and isKeyword() methods to stay within the method limit.
     */
    private function tokenIs(string $type, string $value = ''): bool
    {
        $t = $this->current();
        return $t['type'] === $type && ($value === '' || $t['value'] === $value);
    }

    private function consume(string $type): string
    {
        $t = $this->current();
        if ($t['type'] !== $type) {
            throw new XPathParseException("Expected {$type} but got {$t['type']} ('{$t['value']}') at pos {$this->pos}");
        }
        $this->pos++;
        return $t['value'];
    }

    private function consumeKeyword(string $keyword): void
    {
        $t = $this->current();
        if ($t['type'] !== XPathTokenizer::T_NAME || $t['value'] !== $keyword) {
            throw new XPathParseException("Expected keyword '{$keyword}' but got '{$t['value']}' at pos {$this->pos}");
        }
        $this->pos++;
    }

    // ── Expression grammar ────────────────────────────────────────────────────

    private function parseOr(): Expression
    {
        $left = $this->parseAnd();
        while ($this->tokenIs(XPathTokenizer::T_NAME, 'or')) {
            $this->pos++;
            $left = new BinaryExpression(BinaryOperator::OR, $left, $this->parseAnd());
        }
        return $left;
    }

    private function parseAnd(): Expression
    {
        $left = $this->parseComparison();
        while ($this->tokenIs(XPathTokenizer::T_NAME, 'and')) {
            $this->pos++;
            $left = new BinaryExpression(BinaryOperator::AND, $left, $this->parseComparison());
        }
        return $left;
    }

    private function parseComparison(): Expression
    {
        $left = $this->parseAdditive();

        // XPath 2.0 postfix type operators — lower precedence than additive, higher than and/or.
        if ($this->tokenIs(XPathTokenizer::T_NAME, 'castable')) {
            $this->pos++;
            $this->consumeKeyword('as');
            return new CastableAs($left, $this->consume(XPathTokenizer::T_NAME));
        }

        $ops  = [
            XPathTokenizer::T_EQ => BinaryOperator::EQ,
            XPathTokenizer::T_NE => BinaryOperator::NE,
            XPathTokenizer::T_LT => BinaryOperator::LT,
            XPathTokenizer::T_LE => BinaryOperator::LTE,
            XPathTokenizer::T_GT => BinaryOperator::GT,
            XPathTokenizer::T_GE => BinaryOperator::GTE,
        ];
        $type = $this->current()['type'];
        if (isset($ops[$type])) {
            $this->pos++;
            return new BinaryExpression($ops[$type], $left, $this->parseAdditive());
        }
        return $left;
    }

    private function parseAdditive(): Expression
    {
        $left = $this->parseMultiplicative();
        while ($this->tokenIs(XPathTokenizer::T_PLUS) || $this->tokenIs(XPathTokenizer::T_MINUS)) {
            $op = $this->tokenIs(XPathTokenizer::T_PLUS) ? BinaryOperator::ADD : BinaryOperator::SUB;
            $this->pos++;
            $left = new BinaryExpression($op, $left, $this->parseMultiplicative());
        }
        return $left;
    }

    private function parseMultiplicative(): Expression
    {
        $left = $this->parseUnary();
        while ($this->tokenIs(XPathTokenizer::T_STAR) || $this->tokenIs(XPathTokenizer::T_NAME, 'div') || $this->tokenIs(XPathTokenizer::T_NAME, 'mod')) {
            $op = match (true) {
                $this->tokenIs(XPathTokenizer::T_STAR)          => BinaryOperator::MUL,
                $this->tokenIs(XPathTokenizer::T_NAME, 'div')   => BinaryOperator::DIV,
                default                                          => BinaryOperator::MOD,
            };
            $this->pos++;
            $left = new BinaryExpression($op, $left, $this->parseUnary());
        }
        return $left;
    }

    private function parseUnary(): Expression
    {
        if ($this->tokenIs(XPathTokenizer::T_MINUS)) {
            $this->pos++;
            $operand = $this->parseUnary();
            if ($operand instanceof Literal && is_numeric($operand->value)) {
                return new Literal(-$operand->value);
            }
            return new BinaryExpression(BinaryOperator::SUB, new Literal(0), $operand);
        }
        return $this->parsePrimary();
    }

    private function parsePrimary(): Expression
    {
        $t        = $this->current();
        $nextType = ($this->tokens[$this->pos + 1] ?? ['type' => ''])['type'];

        if ($t['type'] === XPathTokenizer::T_NAME) {
            return match ($t['value']) {
                'some', 'every', 'for' => $this->parseQuantified($t['value']),
                'if'            => $this->parseIfThenElse(),
                default         => $nextType === XPathTokenizer::T_LPAREN
                                    ? $this->parseFunctionCall($t['value'])
                                    : $this->collectPath(),
            };
        }

        // $var/path, $var//path, or $var[n] — collect as a raw path so DOMXPath receives it intact.
        $isVarPath = $t['type'] === XPathTokenizer::T_VARIABLE
            && ($nextType === XPathTokenizer::T_SLASH
                || $nextType === XPathTokenizer::T_DSLASH
                || $nextType === XPathTokenizer::T_LBRACKET);

        if ($isVarPath || in_array($t['type'], [XPathTokenizer::T_LPAREN, XPathTokenizer::T_SLASH, XPathTokenizer::T_DSLASH,
                                                 XPathTokenizer::T_AT, XPathTokenizer::T_DOT, XPathTokenizer::T_DOTDOT,
                                                 XPathTokenizer::T_STAR], true)) {
            return $t['type'] === XPathTokenizer::T_LPAREN ? $this->parseParenOrPath() : $this->collectPath();
        }

        $this->pos++;
        return match ($t['type']) {
            XPathTokenizer::T_STRING   => new Literal($t['value']),
            XPathTokenizer::T_NUMBER   => new Literal(str_contains($t['value'], '.') ? (float) $t['value'] : (int) $t['value']),
            XPathTokenizer::T_VARIABLE => new VariableRef($t['value']),
            default                    => throw new XPathParseException("Unexpected {$t['type']} '{$t['value']}' at pos {$this->pos}"),
        };
    }

    // ── Function calls ────────────────────────────────────────────────────────

    private function parseFunctionCall(string $name): Expression
    {
        $this->pos++; // consume function name
        $this->consume(XPathTokenizer::T_LPAREN);
        $args = $this->parseArgList();
        $this->consume(XPathTokenizer::T_RPAREN);

        return match ($name) {
            'not' => match (count($args)) {
                        1 => $args[0] instanceof Exists ? new NotExists($args[0]->path) : new Not($args[0]),
                        default => throw new XPathParseException('not() requires 1 argument'),
                     },
            'exists'          => new Exists($this->oneArg($args, $name)),
            'count'           => new Count($this->oneArg($args, $name)),
            'sum'             => new Sum($this->oneArg($args, $name)),
            'round'           => new Round($this->oneArg($args, $name)),
            'abs'             => new Abs($this->oneArg($args, $name)),
            'xs:decimal',
            'xs:integer'      => new Decimal($this->oneArg($args, $name)),
            'upper-case'      => new UpperCase($this->oneArg($args, $name)),
            'string'          => new StringCast(count($args) === 0 ? new Path('.') : $this->oneArg($args, $name)),
            'normalize-space' => new NormalizeSpace(count($args) === 0 ? new Path('.') : $this->oneArg($args, $name)),
            'string-length'   => new StringLength(count($args) === 0 ? new Path('.') : $this->oneArg($args, $name)),
            'number'          => new Decimal($this->oneArg($args, $name)),
            'substring'       => match (count($args)) {
                2 => new Substring($args[0], $args[1], null),
                3 => new Substring($args[0], $args[1], $args[2]),
                default => throw new XPathParseException('substring() requires 2 or 3 arguments'),
            },
            'translate'       => count($args) === 3
                ? new Translate($args[0], $args[1], $args[2])
                : throw new XPathParseException('translate() requires 3 arguments'),
            'contains'        => count($args) === 2 ? new Contains($args[0], $args[1])
                                    : throw new XPathParseException('contains() requires 2 arguments'),
            'starts-with'     => count($args) === 2 ? new StartsWith($args[0], $args[1])
                                    : throw new XPathParseException('starts-with() requires 2 arguments'),
            'matches'         => count($args) === 2 ? new Matches($args[0], $args[1])
                                    : throw new XPathParseException('matches() requires 2 arguments'),
            'true'            => new Literal(true),
            'false'           => new Literal(false),
            'u:gln'              => new Checksum(ChecksumAlgorithm::GLN,            $this->oneArg($args, $name)),
            'u:mod11'            => new Checksum(ChecksumAlgorithm::Mod11,          $this->oneArg($args, $name)),
            'u:mod97-0208'       => new Checksum(ChecksumAlgorithm::Mod97BE,        $this->oneArg($args, $name)),
            'u:checkSEOrgnr'     => new Checksum(ChecksumAlgorithm::SEOrgnr,        $this->oneArg($args, $name)),
            'u:abn'              => new Checksum(ChecksumAlgorithm::ABN,            $this->oneArg($args, $name)),
            'u:checkCF'          => new Checksum(ChecksumAlgorithm::CodiceFiscale,  $this->oneArg($args, $name)),
            'u:checkPIVAseIT'    => new Checksum(ChecksumAlgorithm::PIVAseIT,       $this->oneArg($args, $name)),
            'u:checkCodiceIPA'   => new Checksum(ChecksumAlgorithm::CodiceIPA,      $this->oneArg($args, $name)),
            'u:checkDanishCVR'   => new Checksum(ChecksumAlgorithm::DanishCVR,      $this->oneArg($args, $name)),
            'u:TinVerification'  => new Checksum(ChecksumAlgorithm::TINVerification, $this->oneArg($args, $name)),
            default              => new FunctionCall($name, $args),
        };
    }

    /** @param Expression[] $args */
    private function oneArg(array $args, string $fn): Expression
    {
        if (count($args) !== 1) {
            throw new XPathParseException("{$fn}() requires 1 argument, got " . count($args));
        }
        return $args[0];
    }

    /** @return Expression[] */
    private function parseArgList(): array
    {
        if ($this->tokenIs(XPathTokenizer::T_RPAREN)) {
            return [];
        }
        $args = [$this->parseOr()];
        while ($this->tokenIs(XPathTokenizer::T_COMMA)) {
            $this->pos++;
            $args[] = $this->parseOr();
        }
        return $args;
    }

    // ── Structural parsers ────────────────────────────────────────────────────

    private function parseQuantified(string $kind): Expression
    {
        $this->pos++;
        $varName = $this->consume(XPathTokenizer::T_VARIABLE);
        $this->consumeKeyword('in');
        $in = $this->parseOr();
        if ($kind === 'for') {
            $this->consumeKeyword('return');
            return new ForExpression($varName, $in, $this->parseOr());
        }
        $this->consumeKeyword('satisfies');
        $satisfies = $this->parseOr();
        return $kind === 'some'
            ? new Some($varName, $in, $satisfies)
            : new Every($varName, $in, $satisfies);
    }

    private function parseIfThenElse(): Expression
    {
        $this->pos++;
        $this->consume(XPathTokenizer::T_LPAREN);
        $condition = $this->parseOr();
        $this->consume(XPathTokenizer::T_RPAREN);
        $this->consumeKeyword('then');
        $then = $this->parseOr();
        $this->consumeKeyword('else');
        return new IfThenElse($condition, $then, $this->parseOr());
    }

    /**
     * Handles '(' at the primary level.
     *
     * If the parenthesised content is a path union like (cac:A | cac:B), or if the
     * closing ')' is followed by '/', '//', or '[', collect the whole thing as a raw
     * Path delegated to DOMXPath.  Otherwise parse it as a grouped expression.
     */
    private function parseParenOrPath(): Expression
    {
        if ($this->tokenizer !== null && $this->tokenizer->hasPipeAtOuterDepth($this->tokens, $this->pos)) {
            return $this->collectPath();
        }
        $savedPos = $this->pos++;
        $expr     = $this->parseOr();

        // XPath 2.0 sequence constructor: (A, B, ...) — collect all comma-separated items.
        if ($this->tokenIs(XPathTokenizer::T_COMMA)) {
            $items = [$expr];
            while ($this->tokenIs(XPathTokenizer::T_COMMA)) {
                $this->pos++;
                $items[] = $this->parseOr();
            }
            $this->consume(XPathTokenizer::T_RPAREN);
            return new Sequence($items);
        }

        $this->consume(XPathTokenizer::T_RPAREN);
        if ($this->tokenIs(XPathTokenizer::T_SLASH) || $this->tokenIs(XPathTokenizer::T_DSLASH) || $this->tokenIs(XPathTokenizer::T_LBRACKET)) {
            $this->pos = $savedPos;
            return $this->collectPath();
        }
        return $expr;
    }

    /**
     * Collect tokens into a raw XPath path string and wrap in a Path node.
     *
     * Stops at depth 0 on comparison operators, arithmetic operators, stop-keywords
     * (and/or/div/mod/…), COMMA, unbalanced RPAREN/RBRACKET, T_STAR when not after
     * a path separator, or EOF.  Inside [ ] or ( ) all tokens are collected verbatim.
     */
    private function collectPath(): Expression // NOSONAR php:S3776 — state-machine loop; complexity is inherent
    {
        $parts          = [];
        $parenDepth     = 0;
        $bracketDepth   = 0;
        $afterSeparator = false;

        while (!$this->tokenIs(XPathTokenizer::T_EOF)) {
            $t    = $this->current();
            $type = $t['type'];

            if ($type === XPathTokenizer::T_LPAREN)                              { $parenDepth++;   $parts[] = '('; $this->pos++; $afterSeparator = true;  continue; }
            if ($type === XPathTokenizer::T_LBRACKET)                            { $bracketDepth++; $parts[] = '['; $this->pos++; $afterSeparator = true;  continue; }
            if ($type === XPathTokenizer::T_RPAREN  && $parenDepth   === 0)      { break; }
            if ($type === XPathTokenizer::T_RPAREN)                              { $parenDepth--;   $parts[] = ')'; $this->pos++; $afterSeparator = false; continue; }
            if ($type === XPathTokenizer::T_RBRACKET && $bracketDepth === 0)     { break; }
            if ($type === XPathTokenizer::T_RBRACKET)                            { $bracketDepth--; $parts[] = ']'; $this->pos++; $afterSeparator = false; continue; }

            if ($parenDepth > 0 || $bracketDepth > 0) {
                $parts[] = $this->tokenizer !== null ? $this->tokenizer->tokenToString($t) : '';
                $this->pos++;
                continue;
            }

            if ($this->tokenizer !== null && $this->tokenizer->isPathStop($t))   { break; }
            if ($type === XPathTokenizer::T_STAR && !$afterSeparator)            { break; }
            if ($type === XPathTokenizer::T_PIPE) { $parts[] = ' | '; $this->pos++; $afterSeparator = false; continue; }

            $str = $this->tokenizer !== null ? $this->tokenizer->tokenToString($t) : '';
            if ($str === '') { break; }
            $afterSeparator = in_array($type, [XPathTokenizer::T_SLASH, XPathTokenizer::T_DSLASH, XPathTokenizer::T_AT, XPathTokenizer::T_LBRACKET, XPathTokenizer::T_LPAREN], true);
            $parts[] = $str;
            $this->pos++;
        }

        return new Path(implode('', $parts));
    }
}
