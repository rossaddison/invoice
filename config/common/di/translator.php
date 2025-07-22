<?php

declare(strict_types=1);

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;

/*
 * Related logic: see https://github.com/yiisoft/translator/blob/master/config/di.php
 * @var array $params
 * @var array $params['yiisoft/translator']
 * @var string $params['yiisoft/translator']['defaultCategory']
 * Related logic: see yiisoft/validator/messages e.g. '{Property} cannot be blank.' => '{Property} darf nicht leer sein.',
 * @var string $params['yiisoft/translator']['validatorCategory']
 */

return [
    TranslatorInterface::class => [
        'class'         => Translator::class,
        '__construct()' => [
            $params['yiisoft/translator']['locale'],
            $params['yiisoft/translator']['fallbackLocale'],
            $params['yiisoft/translator']['defaultCategory'],
            Reference::optional(EventDispatcherInterface::class),
        ],
        'addCategorySources()' => [
            'categories' => [
                DynamicReference::to(static function (Aliases $aliases) use ($params) {
                    return new CategorySource(
                        $params['yiisoft/translator']['defaultCategory'],
                        new MessageSource($aliases->get('@messages')),
                        new IntlMessageFormatter(),
                    );
                }),
                DynamicReference::to(static function (Aliases $aliases) use ($params) {
                    return new CategorySource(
                        $params['yiisoft/translator']['validatorCategory'],
                        new MessageSource($aliases->get('@validatorMessages')),
                        new IntlMessageFormatter(),
                    );
                }),
            ],
        ],
        'reset' => function () use ($params) {
            /*
             * @var string $params['yiisoft/translator']['locale']
             * @var Translator $this
             */
            $this->setLocale($params['yiisoft/translator']['locale']);
        },
    ],
];
