<?php

declare(strict_types=1);

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Definitions\Reference;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Definitions\DynamicReference;

/**
 * Related logic: see https://github.com/yiisoft/translator/blob/master/config/di.php
 * @var array $params
 * @var array $params['yiisoft/translator']
 * @var string $params['yiisoft/translator']['defaultCategory']
 * Related logic: see yiisoft/validator/messages e.g. '{Property} cannot be blank.' => '{Property} darf nicht leer sein.',
 * @var string $params['yiisoft/translator']['validatorCategory']
 */

$yiisoftTranslatorParams = $params['yiisoft/translator'];

return [
    TranslatorInterface::class => [
        'class' => Translator::class,
        '__construct()' => [
            $yiisoftTranslatorParams['locale'],
            $yiisoftTranslatorParams['fallbackLocale'],
            $yiisoftTranslatorParams['defaultCategory'],
            Reference::optional(EventDispatcherInterface::class),
        ],
        'addCategorySources()' => [
            'categories' => [
                DynamicReference::to(static function (Aliases $aliases) use ($yiisoftTranslatorParams) {
                    return new CategorySource(
                        (string) $yiisoftTranslatorParams['defaultCategory'],
                        new MessageSource($aliases->get('@messages')),
                        new IntlMessageFormatter(),
                    );
                }),
                DynamicReference::to(static function (Aliases $aliases) use ($yiisoftTranslatorParams) {
                    return new CategorySource(
                         (string) $yiisoftTranslatorParams['validatorCategory'],
                        new MessageSource($aliases->get('@validatorMessages')),
                        new IntlMessageFormatter(),
                    );
                }),
            ],
        ],
        'reset' => function () use ($yiisoftTranslatorParams) {
            /**
             * @var Translator $this
             */
            $this->setLocale((string) $yiisoftTranslatorParams['locale']);
        },
    ],
];
