<?php

declare(strict_types=1);

use Yiisoft\Cookies\CookieMiddleware;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\RequestProvider\RequestCatcherMiddleware;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Session\SessionMiddleware;
use Yiisoft\User\Login\Cookie\CookieLoginMiddleware;
use Yiisoft\Yii\Middleware\Locale;
use Yiisoft\Yii\Sentry\SentryMiddleware;

// yii3-i
return [
    'locale' => [
        'locales' => [
            /**
             * Note: key affects RouteArgument _language, value matches locale
             * @see key => value
             */
            'af-ZA' => 'af-ZA', 
            'ar-BH' => 'ar-BH', 
            'az' => 'az-AZ', 
            'de' => 'de-DE', 
            'en' => 'en-US', 
            'es' => 'es-ES',
            'fil' => 'fil-PH',          
            'fr' => 'fr-FR',
            'id' => 'id-ID',
            'it' => 'it-IT',
            'ja' => 'ja-JP', 
            'nl' => 'nl-NL',
            'pl' => 'pl-PL',
            'pt-BR' => 'pt-BR',
            'ru' => 'ru-RU', 
            'sk' => 'sk-SK',
            'uk' => 'uk-UA', 
            'uz' => 'uz-UZ',
            'vi' => 'vi-VN', 
            'zh-CN' => 'zh-CN',
            'zh-TW' => 'zh-TW',
            'zu-ZA' => 'zu-ZA',     
        ],
        'ignoredRequests' => [
            '/gii**',
            '/debug**',
            '/inspect**',
        ],
    ],
    'middlewares' => [
        RequestCatcherMiddleware::class,
        ErrorCatcher::class,
        SentryMiddleware::class,
        SessionMiddleware::class,
        CookieMiddleware::class,
        CookieLoginMiddleware::class,
        Locale::class,
        Router::class,
    ]
];
