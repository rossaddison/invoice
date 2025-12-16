<?php

declare(strict_types=1);

use App\Invoice\PaymentInformation\PaymentInformationController;
use App\Invoice\Libraries\Crypt;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Translator\Translator;

return [
    PaymentInformationController::class => [
        'class' => PaymentInformationController::class,
        '__construct()' => [
            DataResponseFactoryInterface::class => DataResponseFactory::class,
            UrlGeneratorInterface::class => UrlGenerator::class,
            TranslatorInterface::class => Translator::class,
            'telegramToken' => '',
        ],
    ],
];
