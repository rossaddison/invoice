<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;

return [
    ClientInterface::class => [
        'class' => Client::class,
        '__construct()' => [
            [
                // Production: true, Development: false
                'verify' => true,
            ],
        ],
    ],
];
