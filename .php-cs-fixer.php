<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

ini_set('memory_limit', '512M');

// Ensure PHP CS Fixer runs only on PHP 8.3
if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 3) {
    echo "PHP CS Fixer should be run on PHP 8.3 only. Current version: " . PHP_VERSION . "\n";
    echo "Please switch to PHP 8.3 to run PHP CS Fixer.\n";
    exit(1);
}

$root = __DIR__;
$finder = (new Finder())
    ->in([
        $root.'/config',
        $root.'/dev-scripts-psalm-1',
        $root.'/src',
        $root.'/resources/views',
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
    ->setRules([
        '@PER-CS2x0' => true,
    ])
    ->setRiskyAllowed(false)
    ->setUsingCache(true)
    ->setFinder($finder);
