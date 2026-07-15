<?php

declare(strict_types=1);

use App\Middleware\SecurityHeadersMiddleware;

/** @var array{security-headers: array{headers: array<string, string>}} $params */

return [
    SecurityHeadersMiddleware::class => [
        '__construct()' => [
            'headers' => $params['security-headers']['headers'],
        ],
    ],
];
