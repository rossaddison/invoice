<?php

declare(strict_types=1);

return [
    // Cache the compiled FastRoute dispatch table so route compilation
    // is skipped on every request after the first.
    // Clear runtime/cache/routes-cache* whenever routes change (e.g. on deploy).
    'yiisoft/router-fastroute' => [
        'enableCache' => true,
    ],
];
