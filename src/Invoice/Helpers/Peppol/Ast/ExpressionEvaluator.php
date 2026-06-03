<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

use App\Invoice\Helpers\Peppol\CodeList;
use App\Invoice\Helpers\Peppol\Ast\CastableAs;
use App\Invoice\Helpers\Peppol\Ast\ForExpression;
use App\Invoice\Helpers\Peppol\Ast\NormalizeSpace;
use App\Invoice\Helpers\Peppol\Ast\Sequence;
use App\Invoice\Helpers\Peppol\Ast\StringLength;
use App\Invoice\Helpers\Peppol\Ast\Substring;
use App\Invoice\Helpers\Peppol\Ast\Translate;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use RuntimeException;

/**
 * Evaluates a Peppol / EN16931 expression AST against a DOM document.
 *
 * What?  A recursive tree-walker that reduces every Expression node to a PHP-native
 *        value (bool, int, float, string, or array<DOMNode>).
 * Why?   PHP's DOMXPath only supports XPath 1.0 but the Peppol BIS Billing 3.0
 *        Schematron is firmly XPath 2.0.  This evaluator bridges the gap by handling
 *        the XPath 2.0 constructs (exists, upper-case, abs, xs:decimal, matches,
 *        some/every, if-then-else, user-defined checksums) in PHP, while delegating
 *        plain location paths to DOMXPath::evaluate() for the XPath 1.0 subset.
 * When?  Instantiated once per validation run; evaluate() is called once per rule
 *        that uses the AST path (as opposed to rules still living in PeppolValidator).
 * Where? Used by ValidationRule implementations whose test() expression is represented
 *        as an AST rather than a raw DOMXPath string.
 * How?   match(true) dispatch over instanceof checks; three typed coercion helpers
 *        (evalBool / evalString / evalNumeric) handle the XPath implicit-conversion
 *        rules.  Checksum functions are injected at construction time so the evaluator
 *        has no hard dependency on PeppolValidator.
 *
 * Variable scoping for Some / Every
 * ----------------------------------
 * Quantified expressions bind a loop variable (e.g. $x).  The bound value is passed
 * down via the $bindings array.  In the satisfies sub-expression, reference the
 * variable with Path('$x') — the evaluator recognises the leading '$' and looks up
 * the binding rather than passing the string to DOMXPath.
 *
 * Arithmetic operators and range comparisons
 * -------------------------------------------
 * Add, Subtract, Multiply, Divide, Modulo, GreaterThanOrEqual, LessThanOrEqual
 * are all handled.  Divide follows XPath 1.0 IEEE 754 semantics (non-zero divisor
 * yields Infinity, 0 div 0 yields NaN) rather than throwing.
 */

/**
 * @psalm-suppress UnusedClass
 */
final class ExpressionEvaluator // NOSONAR php:S1448 — visitor pattern; each Expression sub-type has its own handler
{
    /**
     * What?  Map from ChecksumAlgorithm->value (e.g. 'u:gln') to a callable that
     *        receives the resolved string and returns bool.
     * Why?   Decouples the evaluator from PeppolValidator's private check* methods;
     *        callers inject real implementations, tests inject stubs.
     * How?   Pass e.g. ['u:gln' => fn(string $v): bool => $validator->checkGLN($v)]
     *        from PeppolValidator (or wherever the checksum logic lives).
     *
     * @var array<string, callable(string): bool>
     */
    private readonly array $checksumHandlers;

    /** @param array<string, callable(string): bool> $checksumHandlers */
    public function __construct(array $checksumHandlers = [])
    {
        $this->checksumHandlers = $checksumHandlers;
    }

    // ── Public entry point ────────────────────────────────────────────────────

    /**
     * Evaluate an expression and return a PHP-native value.
     *
     * Return type by node:
     *   bool    — Exists, NotExists, AndNode, OrNode, Not, Equal, NotEqual,
     *             GreaterThan, LessThan, Contains, StartsWith, Matches,
     *             InCodeList, Checksum, Some, Every
     *   int     — Count
     *   float   — Sum, Round, Abs, Decimal
     *   string  — StringCast, UpperCase
     *   mixed   — IfThenElse (inherits the branch type), Literal
     *   array<int,DOMNode> — Union
     *   mixed   — Path (DOMNodeList, scalar, or binding value)
     *
     * @param array<string, mixed> $bindings Variable bindings injected by Some/Every.
     * @return mixed
     */
    public function evaluate(
        Expression $expr,
        DOMXPath $xpath,
        ?DOMNode $context = null,
        array $bindings = []
    ): mixed {
        return match (true) {
            $expr instanceof Literal     => $expr->value,
            $expr instanceof Path        => $this->evalPath($expr, $xpath, $context),

            $expr instanceof Exists      => $this->evalExists($expr, $xpath, $context, $bindings),
            $expr instanceof NotExists   => !$this->evalExists(
                new Exists($expr->path), $xpath, $context, $bindings
            ),

            $expr instanceof Not              =>
                !$this->evalBool($expr->operand, $xpath, $context, $bindings),

            $expr instanceof BinaryExpression => $this->evalBinary($expr, $xpath, $context, $bindings),

            $expr instanceof VariableRef      => $bindings[$expr->name] ?? '',

            $expr instanceof Count       => $this->evalCount($expr, $xpath, $context, $bindings),
            $expr instanceof Sum         => $this->evalSum($expr,   $xpath, $context, $bindings),
            $expr instanceof Round       =>
                round($this->evalNumeric($expr->value, $xpath, $context, $bindings)),
            $expr instanceof Abs         =>
                abs($this->evalNumeric($expr->value, $xpath, $context, $bindings)),
            $expr instanceof Decimal     =>
                (float) $this->evalString($expr->value, $xpath, $context, $bindings),
            $expr instanceof StringCast  =>
                $this->evalString($expr->value, $xpath, $context, $bindings),
            $expr instanceof UpperCase      =>
                strtoupper($this->evalString($expr->value, $xpath, $context, $bindings)),
            $expr instanceof NormalizeSpace =>
                trim((string) preg_replace('/\s+/', ' ', $this->evalString($expr->value, $xpath, $context, $bindings))),
            $expr instanceof StringLength   =>
                mb_strlen($this->evalString($expr->value, $xpath, $context, $bindings)),
            $expr instanceof Substring      =>
                $this->evalSubstring($expr, $xpath, $context, $bindings),
            $expr instanceof Translate      =>
                $this->evalTranslate($expr, $xpath, $context, $bindings),
            $expr instanceof CastableAs     =>
                $this->evalCastableAs($expr, $xpath, $context, $bindings),
            $expr instanceof Sequence       =>
                $this->evalSequenceNodes($expr, $xpath, $context, $bindings),
            $expr instanceof ForExpression  =>
                $this->evalFor($expr, $xpath, $context, $bindings),

            $expr instanceof Contains    => str_contains(
                $this->evalString($expr->haystack, $xpath, $context, $bindings),
                $this->evalString($expr->needle,   $xpath, $context, $bindings),
            ),
            $expr instanceof StartsWith  => str_starts_with(
                $this->evalString($expr->string, $xpath, $context, $bindings),
                $this->evalString($expr->prefix, $xpath, $context, $bindings),
            ),
            $expr instanceof Matches     => $this->evalMatches($expr,     $xpath, $context, $bindings),
            $expr instanceof InCodeList  => $this->evalInCodeList($expr,  $xpath, $context, $bindings),
            $expr instanceof Checksum    => $this->evalChecksum($expr,    $xpath, $context, $bindings),
            $expr instanceof IfThenElse  => $this->evalIfThenElse($expr,  $xpath, $context, $bindings),
            $expr instanceof Some        => $this->evalSome($expr,        $xpath, $context, $bindings),
            $expr instanceof Every       => $this->evalEvery($expr,       $xpath, $context, $bindings),
            $expr instanceof Union       => $this->evalUnion($expr,       $xpath, $context, $bindings),

            default => throw new RuntimeException(
                'Unhandled expression type: ' . $expr::class
            ),
        };
    }

    /**
     * Evaluate an expression and coerce the result to bool.
     * Convenience wrapper around evaluate() + evalBool() for rule runners.
     *
     * @param array<string, mixed> $bindings
     */
    public function evaluateBool(
        Expression $expr,
        DOMXPath $xpath,
        ?DOMNode $context = null,
        array $bindings = []
    ): bool {
        return $this->evalBool($expr, $xpath, $context, $bindings);
    }

    // ── Typed coercion helpers ────────────────────────────────────────────────

    /**
     * Evaluate and coerce to bool following XPath semantics:
     *   node-set → true if non-empty
     *   string   → true if non-empty
     *   number   → true if non-zero and not NaN
     *
     * @param array<string, mixed> $bindings
     */
    private function evalBool(
        Expression $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): bool {
        /** @psalm-suppress MixedAssignment */
        $v = $this->evaluate($expr, $xpath, $context, $bindings);

        if ($v instanceof DOMNodeList) {
            return $v->length > 0;
        }
        if ($v instanceof DOMNode) {
            return ($v->nodeValue ?? '') !== '';
        }
        if (is_array($v)) {
            return $v !== [];
        }
        if (is_string($v)) {
            return $v !== '';
        }
        return (bool) $v;
    }

    /**
     * Evaluate and coerce to string.
     * For a node-set or node array, returns the string-value of the first node.
     *
     * @param array<string, mixed> $bindings
     */
    private function evalString(
        Expression $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): string {
        /** @psalm-suppress MixedAssignment */
        $v = $this->evaluate($expr, $xpath, $context, $bindings);

        if ($v instanceof DOMNodeList) {
            $first = $v->item(0);
            return $first !== null ? trim($first->nodeValue ?? '') : '';
        }
        if ($v instanceof DOMNode) {
            return trim($v->nodeValue ?? '');
        }
        if (is_array($v)) {
            /** @psalm-suppress MixedAssignment */
            $first = $v[0] ?? null;
            return $first instanceof DOMNode
                ? trim($first->nodeValue ?? '')
                : (string) ($first ?? '');
        }
        return (string) $v;
    }

    /**
     * Evaluate and coerce to float.
     * Resolves via evalString so node-set → first-node string value → float.
     *
     * @param array<string, mixed> $bindings
     */
    private function evalNumeric(
        Expression $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): float {
        /** @psalm-suppress MixedAssignment */
        $v = $this->evaluate($expr, $xpath, $context, $bindings);

        if ($v instanceof DOMNodeList) {
            $first = $v->item(0);
            return $first !== null ? (float) trim($first->nodeValue ?? '0') : 0.0;
        }
        if ($v instanceof DOMNode) {
            return (float) trim($v->nodeValue ?? '0');
        }
        if (is_array($v)) {
            /** @psalm-suppress MixedAssignment */
            $first = $v[0] ?? null;
            return $first instanceof DOMNode
                ? (float) trim($first->nodeValue ?? '0')
                : (float) ($first ?? 0);
        }
        return (float) $v;
    }

    /**
     * Evaluate and return a flat PHP array of DOMNode.
     * Non-node results (scalars) return an empty array.
     *
     * @param array<string, mixed> $bindings
     * @return array<int, DOMNode>
     */
    private function evalNodes(
        Expression $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): array {
        /** @psalm-suppress MixedAssignment */
        $v = $this->evaluate($expr, $xpath, $context, $bindings);
        return $this->toNodeArray($v);
    }

    // ── Specific expression evaluators ────────────────────────────────────────

    private function evalPath(
        Path $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
    ): mixed {
        // DOMXPath::evaluate() returns the natural XPath type:
        //   location path  → DOMNodeList
        //   string()       → string
        //   count()        → float
        return $context !== null
            ? $xpath->evaluate($expr->xpath, $context)
            : $xpath->evaluate($expr->xpath);
    }

    /**
     * Dispatch a binary expression to the appropriate PHP operation.
     *
     * AND and OR short-circuit via PHP's native && / || operators.
     * EQ / NE use node-set comparison semantics (any pair matches).
     * DIV follows IEEE 754 (0÷0 → NaN, x÷0 → ±Inf) rather than throwing.
     *
     * @param array<string, mixed> $bindings
     */
    private function evalBinary(
        BinaryExpression $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): mixed {
        return match ($expr->operator) {
            BinaryOperator::EQ  => $this->evalNodeSetEqual($expr->left, $expr->right, $xpath, $context, $bindings),
            BinaryOperator::NE  => !$this->evalNodeSetEqual($expr->left, $expr->right, $xpath, $context, $bindings),
            BinaryOperator::GT  => $this->evalNumeric($expr->left,  $xpath, $context, $bindings)
                                >  $this->evalNumeric($expr->right, $xpath, $context, $bindings),
            BinaryOperator::GTE => $this->evalNumeric($expr->left,  $xpath, $context, $bindings)
                                >= $this->evalNumeric($expr->right, $xpath, $context, $bindings),
            BinaryOperator::LT  => $this->evalNumeric($expr->left,  $xpath, $context, $bindings)
                                <  $this->evalNumeric($expr->right, $xpath, $context, $bindings),
            BinaryOperator::LTE => $this->evalNumeric($expr->left,  $xpath, $context, $bindings)
                                <= $this->evalNumeric($expr->right, $xpath, $context, $bindings),
            BinaryOperator::AND => $this->evalBool($expr->left,  $xpath, $context, $bindings)
                                && $this->evalBool($expr->right, $xpath, $context, $bindings),
            BinaryOperator::OR  => $this->evalBool($expr->left,  $xpath, $context, $bindings)
                                || $this->evalBool($expr->right, $xpath, $context, $bindings),
            BinaryOperator::ADD => $this->evalNumeric($expr->left,  $xpath, $context, $bindings)
                                +  $this->evalNumeric($expr->right, $xpath, $context, $bindings),
            BinaryOperator::SUB => $this->evalNumeric($expr->left,  $xpath, $context, $bindings)
                                -  $this->evalNumeric($expr->right, $xpath, $context, $bindings),
            BinaryOperator::MUL => $this->evalNumeric($expr->left,  $xpath, $context, $bindings)
                                *  $this->evalNumeric($expr->right, $xpath, $context, $bindings),
            BinaryOperator::DIV => $this->safeDivide(
                $this->evalNumeric($expr->left,  $xpath, $context, $bindings),
                $this->evalNumeric($expr->right, $xpath, $context, $bindings),
            ),
            BinaryOperator::MOD => fmod(
                $this->evalNumeric($expr->left,  $xpath, $context, $bindings),
                $this->evalNumeric($expr->right, $xpath, $context, $bindings),
            ),
        };
    }

    /** XPath div: IEEE 754 — 0÷0 → NaN, x÷0 → ±Inf. */
    private function safeDivide(float $left, float $right): float
    {
        if ($right == 0.0) {
            return $left == 0.0 ? NAN : ($left > 0.0 ? INF : -INF);
        }
        return $left / $right;
    }

    /** @param array<string, mixed> $bindings */
    private function evalExists(
        Exists $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): bool {
        /** @psalm-suppress MixedAssignment */
        $v = $this->evaluate($expr->path, $xpath, $context, $bindings);

        if ($v instanceof DOMNodeList) {
            return $v->length > 0;
        }
        if (is_array($v)) {
            return $v !== [];
        }
        return $v !== null && $v !== '' && $v !== false;
    }

    /**
     * XPath node-set equality semantics: true when any pair of string values matches.
     * Falls back to scalar string comparison when neither side is a node-set.
     *
     * @param array<string, mixed> $bindings
     */
    private function evalNodeSetEqual(
        Expression $left,
        Expression $right,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): bool {
        /** @psalm-suppress MixedAssignment */
        $left  = $this->evaluate($left,  $xpath, $context, $bindings);
        /** @psalm-suppress MixedAssignment */
        $right = $this->evaluate($right, $xpath, $context, $bindings);

        $leftNodes  = $this->toNodeArray($left);
        $rightNodes = $this->toNodeArray($right);

        if ($leftNodes !== [] || $rightNodes !== []) {
            $ls = $leftNodes  !== []
                ? array_map(fn (DOMNode $n) => trim($n->nodeValue ?? ''), $leftNodes)
                : [$this->scalarToString($left)];
            $rs = $rightNodes !== []
                ? array_map(fn (DOMNode $n) => trim($n->nodeValue ?? ''), $rightNodes)
                : [$this->scalarToString($right)];

            foreach ($ls as $l) {
                foreach ($rs as $r) {
                    if ($l === $r) {
                        return true;
                    }
                }
            }
            return false;
        }

        return $this->scalarToString($left) === $this->scalarToString($right);
    }

    /** @param array<string, mixed> $bindings */
    private function evalCount(
        Count $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): int {
        return count($this->evalNodes($expr->path, $xpath, $context, $bindings));
    }

    /** @param array<string, mixed> $bindings */
    private function evalSum(
        Sum $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): float {
        $sum = 0.0;
        foreach ($this->evalNodes($expr->path, $xpath, $context, $bindings) as $node) {
            $sum += (float) trim($node->nodeValue ?? '0');
        }
        return $sum;
    }

    /**
     * XPath 2.0 regex is broadly PCRE-compatible for the patterns used in Peppol.
     * Forward-slashes in the pattern are escaped to avoid breaking the PCRE delimiter.
     *
     * @param array<string, mixed> $bindings
     */
    private function evalMatches(
        Matches $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): bool {
        $value   = $this->evalString($expr->value,   $xpath, $context, $bindings);
        $pattern = $this->evalString($expr->pattern, $xpath, $context, $bindings);
        $pcre    = '/' . str_replace('/', '\\/', $pattern) . '/u';

        return (bool) preg_match($pcre, $value);
    }

    /** @param array<string, mixed> $bindings */
    private function evalInCodeList(
        InCodeList $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): bool {
        return CodeList::contains(
            $expr->list,
            $this->evalString($expr->value, $xpath, $context, $bindings)
        );
    }

    /**
     * Dispatches to the injected checksum handler for the given algorithm.
     *
     * @param array<string, mixed> $bindings
     * @throws RuntimeException When no handler has been registered for the algorithm.
     */
    private function evalChecksum(
        Checksum $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): bool {
        $key     = $expr->algorithm->value;
        $handler = $this->checksumHandlers[$key] ?? null;

        if ($handler === null) {
            throw new RuntimeException(
                "No checksum handler registered for: {$key}. "
                . 'Inject one via the $checksumHandlers constructor argument.'
            );
        }

        return $handler(
            $this->evalString($expr->value, $xpath, $context, $bindings)
        );
    }

    /** @param array<string, mixed> $bindings */
    private function evalIfThenElse(
        IfThenElse $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): mixed {
        return $this->evalBool($expr->condition, $xpath, $context, $bindings)
            ? $this->evaluate($expr->then, $xpath, $context, $bindings)
            : $this->evaluate($expr->else, $xpath, $context, $bindings);
    }

    /**
     * some $v in $in satisfies $satisfies
     * Returns true as soon as one item in the sequence satisfies the predicate.
     *
     * @param array<string, mixed> $bindings
     */
    private function evalSome(
        Some $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): bool {
        /** @psalm-suppress MixedAssignment */
        foreach ($this->evalSequence($expr->in, $xpath, $context, $bindings) as $item) {
            $scope = array_merge($bindings, [$expr->variable => $item]);
            if ($this->evalBool($expr->satisfies, $xpath, $context, $scope)) {
                return true;
            }
        }
        return false;
    }

    /**
     * every $v in $in satisfies $satisfies
     * Returns true for an empty sequence (vacuous truth, per XPath 2.0 semantics).
     *
     * @param array<string, mixed> $bindings
     */
    private function evalEvery(
        Every $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): bool {
        $sequence = $this->evalSequence($expr->in, $xpath, $context, $bindings);
        /** @psalm-suppress MixedAssignment */
        foreach ($sequence as $item) {
            $scope = array_merge($bindings, [$expr->variable => $item]);
            if (!$this->evalBool($expr->satisfies, $xpath, $context, $scope)) {
                return false;
            }
        }
        return true; // empty sequence → vacuously true
    }

    /**
     * for $v in $in return $return
     * Evaluates the return expression for each item in the source sequence,
     * collecting all results into a flat PHP array.
     *
     * @param array<string, mixed> $bindings
     * @return array<int, mixed>
     */
    private function evalFor(
        ForExpression $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): array {
        $result = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($this->evalSequence($expr->in, $xpath, $context, $bindings) as $item) {
            $scope = array_merge($bindings, [$expr->variable => $item]);
            /** @psalm-suppress MixedAssignment */
            $result[] = $this->evaluate($expr->return, $xpath, $context, $scope);
        }
        return $result;
    }

    /**
     * Merge two node-sets, preserving document order and deduplicating by identity.
     *
     * @param array<string, mixed> $bindings
     * @return array<int, DOMNode>
     */
    private function evalUnion(
        Union $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): array {
        $left   = $this->evalNodes($expr->left,  $xpath, $context, $bindings);
        $merged = $left;

        foreach ($this->evalNodes($expr->right, $xpath, $context, $bindings) as $node) {
            foreach ($merged as $existing) {
                if ($existing === $node) {
                    continue 2;
                }
            }
            $merged[] = $node;
        }

        return $merged;
    }

    // ── Sequence helper for Some / Every ─────────────────────────────────────

    /**
     * Reduce an expression to a flat ordered list of items for quantified iteration.
     *
     * - Path  → each matched DOMNode becomes one item.
     * - Union → merged node array.
     * - Literal (string) → split on whitespace; useful when the AST encodes an
     *   inline token list from a Schematron tokenize() that was not collapsed into
     *   an InCodeList node by the parser.
     *
     * @param array<string, mixed> $bindings
     * @return array<int, mixed>
     */
    private function evalSequence(
        Expression $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): array {
        /** @psalm-suppress MixedAssignment */
        $v = $this->evaluate($expr, $xpath, $context, $bindings);

        if ($v instanceof DOMNodeList) {
            $out = [];
            foreach ($v as $node) {
                $out[] = $node;
            }
            return $out;
        }
        if (is_array($v)) {
            return array_values($v);
        }
        if (is_string($v) && $v !== '') {
            // Space-delimited token list from an uncompressed Schematron tokenize().
            return preg_split('/\s+/', trim($v)) ?: [];
        }
        return [];
    }

    // ── String functions ──────────────────────────────────────────────────────

    /** @param array<string, mixed> $bindings */
    private function evalSubstring(
        Substring $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): string {
        $str      = $this->evalString($expr->value, $xpath, $context, $bindings);
        // XPath substring() uses 1-based indexing; convert to 0-based for mb_substr.
        $phpStart = max(0, (int) $this->evalNumeric($expr->start, $xpath, $context, $bindings) - 1);
        if ($expr->length === null) {
            return mb_substr($str, $phpStart);
        }
        return mb_substr($str, $phpStart, (int) $this->evalNumeric($expr->length, $xpath, $context, $bindings));
    }

    /** @param array<string, mixed> $bindings */
    private function evalTranslate(
        Translate $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): string {
        $value  = $this->evalString($expr->value, $xpath, $context, $bindings);
        $from   = $this->evalString($expr->from,  $xpath, $context, $bindings);
        $to     = $this->evalString($expr->to,    $xpath, $context, $bindings);
        $toLen  = mb_strlen($to);
        $result = '';
        foreach (mb_str_split($value) as $char) {
            $pos = mb_strpos($from, $char);
            if ($pos === false) {
                $result .= $char;
            } elseif ($pos < $toLen) {
                $result .= mb_substr($to, $pos, 1);
            }
            // char whose position in $from >= len($to) is deleted — XPath spec
        }
        return $result;
    }

    /** @param array<string, mixed> $bindings */
    private function evalCastableAs(
        CastableAs $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): bool {
        $value = trim($this->evalString($expr->value, $xpath, $context, $bindings));
        return match ($expr->typeName) {
            'xs:date'            => $this->isValidXsDate($value),
            'xs:integer',
            'xs:long',
            'xs:int',
            'xs:short',
            'xs:byte'            => ctype_digit(ltrim($value, '-+')) && $value !== '' && $value !== '-' && $value !== '+',
            'xs:decimal',
            'xs:float',
            'xs:double'          => is_numeric($value),
            'xs:boolean'         => in_array($value, ['true', 'false', '1', '0'], true),
            'xs:string'          => true,
            default              => false,
        };
    }

    /**
     * Merge every item in a sequence constructor into a single node array.
     * evalBool treats a non-empty array as true, so (A, B) is true when any
     * item produces at least one node — the XPath 2.0 EBV for node sequences.
     *
     * @param array<string, mixed> $bindings
     * @return array<int, DOMNode>
     */
    private function evalSequenceNodes(
        Sequence $expr,
        DOMXPath $xpath,
        ?DOMNode $context,
        array $bindings
    ): array {
        $nodes = [];
        foreach ($expr->items as $item) {
            array_push($nodes, ...$this->evalNodes($item, $xpath, $context, $bindings));
        }
        return $nodes;
    }

    private function isValidXsDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y);
    }

    // ── Scalar utilities ──────────────────────────────────────────────────────

    /**
     * Extract a flat array of DOMNode from any evaluated result.
     *
     * @param mixed $value
     * @return array<int, DOMNode>
     */
    private function toNodeArray(mixed $value): array
    {
        if ($value instanceof DOMNodeList) {
            $out = [];
            foreach ($value as $node) {
                $out[] = $node;
            }
            return $out;
        }
        if ($value instanceof DOMNode) {
            return [$value];
        }
        if (is_array($value)) {
            return array_values(
                array_filter($value, fn (mixed $v) => $v instanceof DOMNode)
            );
        }
        return [];
    }

    /**
     * Coerce any scalar or first-node-of-set to string without going through
     * the full evalString() path (avoids re-evaluating an already-resolved value).
     */
    private function scalarToString(mixed $value): string
    {
        if ($value instanceof DOMNodeList) {
            $first = $value->item(0);
            return $first !== null ? trim($first->nodeValue ?? '') : '';
        }
        if ($value instanceof DOMNode) {
            return trim($value->nodeValue ?? '');
        }
        if (is_array($value)) {
            /** @psalm-suppress MixedAssignment */
            $first = $value[0] ?? null;
            return $first instanceof DOMNode
                ? trim($first->nodeValue ?? '')
                : (string) ($first ?? '');
        }
        return (string) $value;
    }
}
