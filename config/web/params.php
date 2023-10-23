<?php

declare(strict_types=1);

use Yiisoft\Cookies\CookieMiddleware;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Session\SessionMiddleware;
use Yiisoft\User\Login\Cookie\CookieLoginMiddleware;
use Yiisoft\Yii\Middleware\Locale;
use Yiisoft\Yii\Sentry\SentryMiddleware;

// yii3-i
return [
    'locale' => [
        'locales' => [
            'af' => 'af', 
            'ar' => 'ar-BH', 
            'az' => 'az-AZ', 
            'de' => 'de-DE', 
            'en' => 'en-US', 
            'es' => 'es-ES',
            'fr' => 'fr-FR',
            'id' => 'id-ID', 
            'ja' => 'ja-JP', 
            'nl' => 'nl-NL', 
            'ru' => 'ru-RU', 
            'sk' => 'sk-SK',
            'uk' => 'uk-UA', 
            'uz' => 'uz-UZ',
            'vi' => 'vi-VN', 
            'zh' => 'zh-CN'
        ],
        'ignoredRequests' => [
            '/debug**',
            '/inspect**',
        ],
    ],
    'middlewares' => [
        ErrorCatcher::class,
        SentryMiddleware::class,
        SessionMiddleware::class,
        CookieMiddleware::class,
        CookieLoginMiddleware::class,
        Locale::class,
        Router::class,
    ]
];
