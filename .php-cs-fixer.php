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
        $root . '/Tests',
    ])
    ->append([
        $root . '/public/index.php',
    ]);

return (new Config())
    ->setCacheFile(__DIR__ . '/runtime/cache/.php-cs-fixer.cache')
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS2.0' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);
