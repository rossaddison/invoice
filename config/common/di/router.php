<?php

declare(strict_types=1);

use App\Middleware\CsrfExemptMiddleware;
use App\Middleware\CsrfFailureHandler;
use Yiisoft\Config\Config;
use Yiisoft\Csrf\CsrfTokenMiddleware;
use Yiisoft\DataResponse\Middleware\DataResponseMiddleware;
use Yiisoft\DataResponse\Formatter\JsonFormatter;
use Yiisoft\Definitions\Reference;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\Group;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * @var Config $config
 * @var array $params
 * @var array $params['yiisoft/router-fastroute']
 * @var bool $params['yiisoft/router-fastroute']['encodeRaw']
 * @psalm-suppress MixedArgument $routes
 */

return [
    UrlGeneratorInterface::class => [
        'class' => UrlGenerator::class,
        'setEncodeRaw()' => [$params['yiisoft/router-fastroute']['encodeRaw']],
        'setDefaultArgument()' => ['_language', 'en'],
    ],
    DataResponseMiddleware::class => [
        'class' => DataResponseMiddleware::class,
        '__construct()' => [
            'formatter' => Reference::to(JsonFormatter::class),
        ],
    ],
    // Without this, CsrfTokenMiddleware falls back to its own default
    // failure response -- a bare 422 with no styling. See
    // CsrfFailureHandler's own docblock for the full story.
    CsrfTokenMiddleware::class => [
        '__construct()' => [
            'failureHandler' => Reference::to(CsrfFailureHandler::class),
        ],
    ],
    RouteCollectionInterface::class => static function (RouteCollectorInterface $collector) use ($config) {
        $routes = $config->get('routes');
        $collector
            ->middleware(CsrfExemptMiddleware::class)
            ->middleware(DataResponseMiddleware::class)
            ->addRoute(
                Group::create('/{_language}')
                    ->routes(...$routes),
            );
        return new RouteCollection($collector);
    },
];
