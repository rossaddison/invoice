<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * Rector applied to code 19/02/2025
 * by running c:\wamp64\www\invoice>php ./vendor/bin/rector process src --dry-run
 * To apply ran: Above command without --dry-run switch
 */

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPhpSets(php83: true);
