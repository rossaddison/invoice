<?php

declare(strict_types=1);

use App\Middleware\ContentSecurityPolicyMiddleware;

/** @var array $params */

return [
    ContentSecurityPolicyMiddleware::class => [
        '__construct()' => [
            'policy' => $params['csp']['policy'],
        ],
    ],
];
