<?php

declare(strict_types=1);

use App\Service\ClientIpResolver;

/** @var array $params */

return [
    ClientIpResolver::class => [
        '__construct()' => [
            'trustedProxies' => $params['app.trustedProxies'],
        ],
    ],
];
