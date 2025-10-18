<?php

declare(strict_types=1);

use App\Invoice\Inv\InvController;
use App\Invoice\Libraries\Crypt;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Translator\Translator;

return [
    InvController::class => [
        'class' => InvController::class,
        '__construct()' => [
            'crypt' => new Crypt(),
            'salt' => (new Crypt())->salt(),
            DataResponseFactoryInterface::class => DataResponseFactory::class,
            UrlGeneratorInterface::class => UrlGenerator::class,
            TranslatorInterface::class => Translator::class,
        ],
    ],
];
