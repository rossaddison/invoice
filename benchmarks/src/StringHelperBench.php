<?php

declare(strict_types=1);

/**
 * String Helper Benchmark Suite
 *
 * Measures Yiisoft\Strings utilities — StringHelper (static methods),
 * Inflector (case/plural transforms), WildcardPattern (glob-style matching),
 * and CombinedRegexp (multi-pattern matching with a single preg call).
 *
 * These are used heavily throughout Yii3-i for route parameter processing,
 * translation key building, and attribute/column name derivation.
 *
 * @return array<string, array{fn:callable, revs:int, warmup:int, its:int}>
 */

use Yiisoft\Strings\CombinedRegexp;
use Yiisoft\Strings\Inflector;
use Yiisoft\Strings\MemoizedCombinedRegexp;
use Yiisoft\Strings\StringHelper;
use Yiisoft\Strings\WildcardPattern;

return static function (): array {
    $inflector = new Inflector();

    // WildcardPattern: compiled once, reused
    $wildcardAny    = new WildcardPattern('invoice/*');
    $wildcardSuffix = new WildcardPattern('*.php');

    // CombinedRegexp: multiple patterns merged into one preg call.
    // Patterns are raw regex bodies — no outer delimiters; CombinedRegexp adds /…/ itself.
    $combined = new CombinedRegexp([
        '^invoice/\d+$',
        '^quote/\d+$',
        '^client/[a-z]+$',
        '^product/[a-z\-]+$',
        '^setting/[a-z]+$',
    ]);
    $memoized = new MemoizedCombinedRegexp($combined);

    return [
        // ── StringHelper static methods ───────────────────────────────────────
        'benchStartsWith' => [
            'fn'     => static fn() => StringHelper::startsWith('invoice/view/42', 'invoice'),
            'revs'   => 5000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchEndsWith' => [
            'fn'     => static fn() => StringHelper::endsWith('InvoiceController.php', '.php'),
            'revs'   => 5000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchTruncateEnd' => [
            'fn'     => static fn() => StringHelper::truncateEnd(
                'This is a long invoice description that needs to be shortened',
                40,
            ),
            'revs'   => 3000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchCountWords' => [
            'fn'     => static fn() => StringHelper::countWords(
                'Yii3 is a fast secure and professional PHP framework',
            ),
            'revs'   => 5000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── Inflector ─────────────────────────────────────────────────────────
        'benchToSnakeCase' => [
            'fn'     => static fn() => $inflector->toSnakeCase('InvoiceLineItem'),
            'revs'   => 3000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchToCamelCase' => [
            'fn'     => static fn() => $inflector->toCamelCase('invoice_line_item'),
            'revs'   => 3000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchToPascalCase' => [
            'fn'     => static fn() => $inflector->toPascalCase('invoice_line_item'),
            'revs'   => 3000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchToPlural' => [
            'fn'     => static fn() => $inflector->toPlural('invoice'),
            'revs'   => 2000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchToSingular' => [
            'fn'     => static fn() => $inflector->toSingular('invoices'),
            'revs'   => 2000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchClassToTable' => [
            'fn'     => static fn() => $inflector->classToTable('InvoiceLineItem'),
            'revs'   => 3000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── WildcardPattern ───────────────────────────────────────────────────
        'benchWildcardMatch_hit' => [
            'fn'     => static fn() => $wildcardAny->match('invoice/view'),
            'revs'   => 5000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchWildcardMatch_miss' => [
            'fn'     => static fn() => $wildcardSuffix->match('InvoiceController'),
            'revs'   => 5000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── CombinedRegexp (5 patterns merged into one preg_match) ────────────
        'benchCombinedRegexp_hit' => [
            'fn'     => static fn() => $combined->matches('invoice/42'),
            'revs'   => 3000,
            'warmup' => 3,
            'its'    => 7,
        ],

        'benchMemoizedRegexp_hit' => [
            'fn'     => static fn() => $memoized->matches('invoice/42'),
            'revs'   => 3000,
            'warmup' => 3,
            'its'    => 7,
        ],
    ];
};
