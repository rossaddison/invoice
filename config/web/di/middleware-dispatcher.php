<?php

declare(strict_types=1);

/**
 * @var array $params
 */
use Yiisoft\Definitions\Reference;
use Yiisoft\Input\Http\Request\Catcher\RequestCatcherParametersResolver;
use Yiisoft\Middleware\Dispatcher\CompositeParametersResolver;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;

return [
    ParametersResolverInterface::class => [
        'class' => CompositeParametersResolver::class,
        '__construct()' => [
            Reference::to(RequestCatcherParametersResolver::class),
        ],
    ],
];
