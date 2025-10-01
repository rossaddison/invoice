<?php

declare(strict_types=1);

use App\Handler\NotFoundHandler;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Yii\Middleware\Locale;
use Yiisoft\Yii\Middleware\Subfolder;

/**
 * @var array $params
 * @var array $params['locale']
 * @var array $params['locale']['locales']
 * @var array $params['locale']['ignoredRequests']
 * @var string|null $_ENV['BASE_URL']
 */

$construct = '__construct()';

return [
    Yiisoft\Yii\Http\Application::class => [
        $construct => [
            'dispatcher' => DynamicReference::to([
                'class' => MiddlewareDispatcher::class,
                'withMiddlewares()' => [$params['middlewares']],
            ]),
            'fallbackHandler' => Reference::to(NotFoundHandler::class),
        ],
    ],
    Locale::class => [
        $construct => [
            'supportedLocales' => $params['locale']['locales'],
            'ignoredRequestUrlPatterns' => $params['locale']['ignoredRequests'],
        ],
    ],
    Subfolder::class => [
        $construct => [
            'prefix' => !empty(trim($_ENV['BASE_URL'] ?? '', '/')) ? $_ENV['BASE_URL'] : null,
        ],
    ],
];
