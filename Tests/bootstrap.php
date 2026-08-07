<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Rewriting PHPUnit's own package (and the core internals it's built on)
// corrupts its class hierarchy — e.g. TestStatus\Error stops matching its
// own readonly parent TestStatus\Known once bypass-finals strips
// 'readonly' from one but not the other, depending on load order. Deny
// those specifically; everything else (this app's own final classes, and
// third-party ones like Yiisoft\Router\CurrentRoute that tests do mock)
// stays bypassable.
DG\BypassFinals::denyPaths([
    '*/vendor/phpunit/*',
    '*/vendor/sebastian/*',
    '*/vendor/myclabs/*',
    '*/vendor/theseer/*',
    '*/vendor/phar-io/*',
]);
DG\BypassFinals::enable();
