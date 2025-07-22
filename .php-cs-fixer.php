<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

ini_set('memory_limit', '512M');

$root = __DIR__;
$finder = (new Finder())
    ->in([
        $root. '/config',
        $root. '/dev-scripts-psalm-1',
        $root . '/src',
        $root . '/resources/views',
    ])
    ->append([
        $root . '/public/index.php',
    ]);

return (new Config())
    ->setCacheFile(__DIR__ . '/runtime/cache/.php-cs-fixer.cache')
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS2.0' => true,
        '@Symfony' => true,
        'no_unused_imports' => true,
        'array_syntax' => [
            'syntax' => 'short'
        ],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha'
        ],
        'single_quote' => true,
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal'
        ],
        'blank_line_before_statement' => [
            'statements' => ['return']
        ],
        'method_chaining_indentation' => true,
    ])
    ->setFinder($finder);
