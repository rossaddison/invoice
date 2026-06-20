<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\SuiteConfig;

return new ApplicationConfig(
    src: ['src'],
    suites: [
        new SuiteConfig(
            name: 'Unit',
            location: ['Tests/Testo'],
        ),
        // Inline tests and benchmarks embedded in source files
        new SuiteConfig(
            name: 'Sources',
            location: ['src'],
        ),
    ],
);
