<?php

declare(strict_types=1);

use App\Invoice\Quote\QuoteController;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

return [
    QuoteController::class => [
        'class' => QuoteController::class,
        '__construct()' => [
            DataResponseFactoryInterface::class => DataResponseFactory::class,
        ],
    ],
];
