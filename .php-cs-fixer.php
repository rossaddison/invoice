<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

ini_set('memory_limit', '512M');

$root = __DIR__;
$finder =  new Finder()
    ->in([
        $root.'/config',
        $root.'/src',
        $root.'/Tests',
    ])
    // resources/views is deliberately NOT included: its view partials
    // follow the one-space-per-nesting-level indentation convention
    // documented in _html_php_conventions.php, which PSR12's indentation
    // rule can never satisfy -- confirmed live 2026-09-04, the first time
    // this workflow's own plumbing bugs were fixed enough to let it
    // actually run: it stripped nearly all leading whitespace from 1132
    // files rather than reformatting them sensibly, destroying the
    // documented nesting structure. Do not add resources/views back here
    // without also reconciling that convention with PSR12 first.
    // relative not absolute paths
    ->exclude([
        'invoice/del',
        'invoice/generatorrelation',
    ])
    ->append([
        $root.'/public/index.php',
    ]);

return  new Config()
    ->setCacheFile(__DIR__ . '/runtime/cache/.php-cs-fixer.cache')
    ->setParallelConfig(ParallelConfigFactory::detect(
        // $filesPerProcess
        10,
        // $processTimeout in seconds
        200,
        // $maxProcesses
        10
    ))
        
    /**
     * Related logic:
     *
     * https://github.com/PHP-CS-Fixer/PHP-CS-Fixer
     * vendor\friendsofphp\php-cs-fixer\src\RuleSet\Sets
     * https://cs.symfony.com/doc/usage.html
     *
     * e.g. The PSR12 set inherits from the PSR2 set the following line
     * 'single_blank_line_at_eof => true'
     *
     * To run a single check without changes at command line: e.g.
     * php vendor/bin/php-cs-fixer fix . --rules=single_blank_line_at_eof --verbose --dry-run
     *
     */
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
