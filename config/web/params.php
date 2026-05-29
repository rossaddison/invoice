<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Assets\BootstrapAsset;
use Yiisoft\Bootstrap5\Assets\BootstrapCdnAsset;
use App\Invoice\Prometheus\PrometheusMiddleware;
use Yiisoft\Cookies\CookieMiddleware;
use Yiisoft\Csrf\CsrfTokenMiddleware;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\RequestProvider\RequestCatcherMiddleware;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Session\SessionMiddleware;
use Yiisoft\User\Login\Cookie\CookieLoginMiddleware;
use App\Middleware\PageOutOfRangeMiddleware;
use Yiisoft\Yii\Middleware\Locale;

// yii3-i
return [
    'locale' => [
        'locales' => [
            /**
             * Note: key affects RouteArgument _language, value matches locale
             * Related logic: see key => value
             */
            'af-ZA' => 'af-ZA',
            'ar-BH' => 'ar-BH',
            'az' => 'az-AZ',
            'be-BY' => 'be-BY',
            'bs' => 'bs-BS',
            'de' => 'de-DE',
            'en' => 'en-US',
            'es' => 'es-ES',
            'fil' => 'fil-PH',
            'fr' => 'fr-FR',
            'gd-GB' => 'gd-GB',
            'ha-NG' => 'ha-NG',
            'he-IL' => 'he-IL',
            'ig-NG' => 'ig-NG',
            'id' => 'id-ID',
            'it' => 'it-IT',
            'ja' => 'ja-JP',
            'nl' => 'nl-NL',
            'pl' => 'pl-PL',
            'pt-BR' => 'pt-BR',
            'ru' => 'ru-RU',
            'sk' => 'sk-SK',
            'sl' => 'sl-SL',
            'uk' => 'uk-UA',
            'uz' => 'uz-UZ',
            'vi' => 'vi-VN',
            'yo-NG' => 'yo-NG',
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
        PrometheusMiddleware::class,
        SessionMiddleware::class,
        CsrfTokenMiddleware::class,
        CookieMiddleware::class,
        CookieLoginMiddleware::class,
        Locale::class,
        PageOutOfRangeMiddleware::class,
        Router::class,
    ],
    'yiisoft/widget' => [
        'defaultTheme' => 'bootstrap5',
    ],
    'yiisoft/assets' => [
        'assetManager' => [
            'customizedBundles' => [
                // Bootstrap CSS is already compiled into style.css — suppress the
                // duplicate load that yii-bootstrap5 widgets auto-register.
                // main.php uses BootstrapCssOnlyAsset / BootstrapCdnCssOnlyAsset instead.
                BootstrapAsset::class => [
                    'css' => [],
                ],
                BootstrapCdnAsset::class => [
                    'css' => [],
                ],
            ],
        ],
    ],
];
