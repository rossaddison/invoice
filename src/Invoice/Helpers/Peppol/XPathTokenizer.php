<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Exception\XPathParseException;

/**
 * Converts a raw XPath 2.0 expression string into a flat token array,
 * and provides token-analysis helpers for XPathParser.
 *
 * Extracted from XPathParser to keep each class under the 20-method limit
 * and to isolate lexical-analysis complexity from grammar rules.
 *
 * Token shape: array{type: string, value: string}
 */
final class XPathTokenizer
{
    public const T_STRING   = 'STRING';
    public const T_NUMBER   = 'NUMBER';
    public const T_VARIABLE = 'VARIABLE';
    public const T_NAME     = 'NAME';
    public const T_LPAREN   = 'LPAREN';
    public const T_RPAREN   = 'RPAREN';
    public const T_LBRACKET = 'LBRACKET';
    public const T_RBRACKET = 'RBRACKET';
    public const T_COMMA    = 'COMMA';
    public const T_SLASH    = 'SLASH';
    public const T_DSLASH   = 'DSLASH';
    public const T_AT       = 'AT';
    public const T_DOT      = 'DOT';
    public const T_DOTDOT   = 'DOTDOT';
    public const T_STAR     = 'STAR';
    public const T_PIPE     = 'PIPE';
    public const T_PLUS     = 'PLUS';
    public const T_MINUS    = 'MINUS';
    public const T_EQ       = 'EQ';
    public const T_NE       = 'NE';
    public const T_LT       = 'LT';
    public const T_LE       = 'LE';
    public const T_GT       = 'GT';
    public const T_GE       = 'GE';
    public const T_EOF      = 'EOF';

    /** Keywords that terminate a raw path expression at depth 0. */
    public const STOP_KEYWORDS = [
        'and', 'or', 'div', 'mod', 'some', 'every',
        'if', 'then', 'else', 'satisfies', 'in', 'return',
    ];

    private const STOP_TYPES = [
        self::T_EQ, self::T_NE, self::T_LT, self::T_LE,
        self::T_GT, self::T_GE, self::T_PLUS, self::T_MINUS, self::T_COMMA,
    ];

    /**
     * Tokenise $input into an array of {type, value} pairs ending with T_EOF.
     *
     * @return array<int, array{type: string, value: string}>
     * @throws XPathParseException on an unrecognised character.
     */
    public function tokenize(string $input): array
    {
        $tokens = [];
        $len    = strlen($input);
        $i      = 0;

        while ($i < $len) {
            if (ctype_space($input[$i])) { $i++; continue; }
            $c = $input[$i];

            if ($this->isQuote($c))                           { $tokens[] = $this->scanString($input, $i); continue; }
            if ($this->isNumberStart($c, $input, $i, $len))   { $tokens[] = $this->scanNumber($input, $i); continue; }
            if ($c === '$')                                    { $tokens[] = $this->scanVariable($input, $i, $len); continue; }
            if ($this->isNameStart($c))                        { $tokens[] = $this->scanName($input, $i, $len); continue; }

            $two = $this->matchTwoChar($input, $i, $len);
            if ($two !== null) { $tokens[] = $two; $i += 2; continue; }

            $single = $this->matchSingleChar($c);
            if ($single !== null) { $tokens[] = $single; $i++; continue; }

            throw new XPathParseException("Unexpected character '{$c}' at offset {$i} in: {$input}");
        }

        $tokens[] = ['type' => self::T_EOF, 'value' => ''];
        return $tokens;
    }

    /**
     * True when the token terminates a raw path collection at depth 0.
     * Does not cover T_STAR (context-dependent) or T_PIPE (used inside paths).
     *
     * @param array{type: string, value: string} $t
     */
    public function isPathStop(array $t): bool
    {
        return in_array($t['type'], self::STOP_TYPES, true)
            || ($t['type'] === self::T_NAME && in_array($t['value'], self::STOP_KEYWORDS, true));
    }

    /**
     * Scan the token array from $startPos (which must point at a T_LPAREN) and
     * return true when a T_PIPE appears at depth 0 with no $variable or structural
     * keyword at that depth — i.e., the parenthesised sub-expression looks like a
     * path union (cac:A | cac:B) rather than a grouped arithmetic/boolean expression.
     *
     * @param array<int, array{type: string, value: string}> $tokens
     */
    public function hasPipeAtOuterDepth(array $tokens, int $startPos): bool
    {
        $depth = 0;
        for ($i = $startPos + 1, $n = count($tokens); $i < $n; $i++) {
            $t = $tokens[$i];
            if ($this->isOpenBracket($t['type']))                  { $depth++; continue; }
            if ($this->isCloseBracket($t['type']) && $depth === 0) { break; }
            if ($this->isCloseBracket($t['type']))                 { $depth--; continue; }
            if ($depth > 0)                                        { continue; }
            if ($t['type'] === self::T_PIPE)                       { return true; }
            if ($this->isNonPathPrimary($t))                       { return false; }
        }
        return false;
    }

    /**
     * Convert a token back to the source string it was scanned from.
     *
     * @param array{type: string, value: string} $t
     */
    public function tokenToString(array $t): string
    {
        return match ($t['type']) {
            self::T_STRING   => "'" . $t['value'] . "'",
            self::T_NUMBER,
            self::T_NAME     => $t['value'],
            self::T_VARIABLE => '$' . $t['value'],
            self::T_SLASH    => '/',
            self::T_DSLASH   => '//',
            self::T_AT       => '@',
            self::T_DOT      => '.',
            self::T_DOTDOT   => '..',
            self::T_STAR     => '*',
            self::T_PIPE     => '|',
            self::T_PLUS     => '+',
            self::T_MINUS    => '-',
            self::T_EQ       => '=',
            self::T_NE       => '!=',
            self::T_LT       => '<',
            self::T_LE       => '<=',
            self::T_GT       => '>',
            self::T_GE       => '>=',
            self::T_COMMA    => ',',
            self::T_LPAREN   => '(',
            self::T_RPAREN   => ')',
            self::T_LBRACKET => '[',
            self::T_RBRACKET => ']',
            default          => '',
        };
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function isQuote(string $c): bool
    {
        return $c === '\'' || $c === '"';
    }

    private function isNameStart(string $c): bool
    {
        return ctype_alpha($c) || $c === '_';
    }

    private function isNumberStart(string $c, string $input, int $i, int $len): bool
    {
        return ctype_digit($c) || ($c === '.' && $i + 1 < $len && ctype_digit($input[$i + 1]));
    }

    private function isOpenBracket(string $type): bool
    {
        return $type === self::T_LPAREN || $type === self::T_LBRACKET;
    }

    private function isCloseBracket(string $type): bool
    {
        return $type === self::T_RPAREN || $type === self::T_RBRACKET;
    }

    /**
     * True when a depth-0 token inside (…) indicates the content is not a pure
     * path — i.e. it contains a $variable or a structural keyword.
     *
     * @param array{type: string, value: string} $t
     */
    private function isNonPathPrimary(array $t): bool
    {
        return $t['type'] === self::T_VARIABLE
            || ($t['type'] === self::T_NAME && in_array($t['value'], ['some', 'every', 'if', 'then', 'else'], true));
    }

    /** @return array{type: string, value: string} */
    private function scanString(string $input, int &$i): array
    {
        $quote = $input[$i++];
        $start = $i;
        while ($i < strlen($input) && $input[$i] !== $quote) { $i++; }
        $value = substr($input, $start, $i - $start);
        $i++;
        return ['type' => self::T_STRING, 'value' => $value];
    }

    /** @return array{type: string, value: string} */
    private function scanNumber(string $input, int &$i): array
    {
        $start = $i;
        while ($i < strlen($input) && (ctype_digit($input[$i]) || $input[$i] === '.')) { $i++; }
        return ['type' => self::T_NUMBER, 'value' => substr($input, $start, $i - $start)];
    }

    /** @return array{type: string, value: string} */
    private function scanVariable(string $input, int &$i, int $len): array
    {
        $i++;
        $start = $i;
        while ($i < $len && (ctype_alnum($input[$i]) || $input[$i] === '_' || $input[$i] === '-')) { $i++; }
        return ['type' => self::T_VARIABLE, 'value' => substr($input, $start, $i - $start)];
    }

    /**
     * Scans an XML NCName, optionally namespace-prefixed (ns:local),
     * and hyphenated names such as upper-case or normalize-space.
     *
     * @return array{type: string, value: string}
     */
    private function scanName(string $input, int &$i, int $len): array
    {
        $start = $i;
        while ($i < $len && (ctype_alnum($input[$i]) || $input[$i] === '_' || $input[$i] === '-' || $input[$i] === '.')) { $i++; }
        if ($i < $len && $input[$i] === ':' && $i + 1 < $len && $this->isNameStart($input[$i + 1])) {
            $i++;
            while ($i < $len && (ctype_alnum($input[$i]) || $input[$i] === '_' || $input[$i] === '-' || $input[$i] === '.')) { $i++; }
        }
        return ['type' => self::T_NAME, 'value' => substr($input, $start, $i - $start)];
    }

    /**
     * Attempt to match a two-character token starting at $i.
     * Returns null when fewer than two characters remain or no match.
     *
     * @return array{type: string, value: string}|null
     */
    private function matchTwoChar(string $input, int $i, int $len): ?array
    {
        if ($i + 1 >= $len) {
            return null;
        }
        return match ($input[$i] . $input[$i + 1]) {
            '//' => ['type' => self::T_DSLASH, 'value' => '//'],
            '!=' => ['type' => self::T_NE,     'value' => '!='],
            '<=' => ['type' => self::T_LE,     'value' => '<='],
            '>=' => ['type' => self::T_GE,     'value' => '>='],
            '..' => ['type' => self::T_DOTDOT, 'value' => '..'],
            default => null,
        };
    }

    /** @return array{type: string, value: string}|null */
    private function matchSingleChar(string $c): ?array
    {
        $map = [
            '(' => self::T_LPAREN,  ')' => self::T_RPAREN,
            '[' => self::T_LBRACKET, ']' => self::T_RBRACKET,
            ',' => self::T_COMMA,
            '/' => self::T_SLASH,   '@' => self::T_AT,
            '.' => self::T_DOT,     '*' => self::T_STAR,
            '|' => self::T_PIPE,    '+' => self::T_PLUS,   '-' => self::T_MINUS,
            '=' => self::T_EQ,      '<' => self::T_LT,     '>' => self::T_GT,
        ];
        return isset($map[$c]) ? ['type' => $map[$c], 'value' => $c] : null;
    }
}
