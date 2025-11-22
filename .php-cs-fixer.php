<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

ini_set('memory_limit', '512M');

$root = __DIR__;
$finder = (new Finder())
    ->in([
        $root.'/config',
        $root.'/dev-scripts-psalm-1',
        $root.'/src',
        $root.'/resources/views',
        $root.'/Tests',
    ])
    // relative not absolute paths
    ->exclude([
        'invoice/del',
        'invoice/generatorrelation',
    ])
    ->append([
        $root.'/public/index.php',
    ]);

return (new Config())
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
