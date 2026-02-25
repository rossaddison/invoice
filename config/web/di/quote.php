<?php

declare(strict_types=1);

use App\Invoice\Quote\QuoteController;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactory;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

return [
    QuoteController::class => [
        'class' => QuoteController::class,
        '__construct()' => [
            DataResponseFactoryInterface::class => DataResponseFactory::class,
        ],
    ],
];
