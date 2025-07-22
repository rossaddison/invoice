<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\TranslatorExtractor\CategorySource;
use Yiisoft\TranslatorExtractor\Extractor;

/** @var array $params */

return [
    Extractor::class => [
        '__construct()' => [
            [
                DynamicReference::to([
                    'class' => CategorySource::class,
                    '__construct()' => [
                        'app',
                        'messageReader' => DynamicReference::to(static fn(Aliases $aliases) => new MessageSource($aliases->get('@messages'))),
                        'messageWriter' => DynamicReference::to(static fn(Aliases $aliases) => new MessageSource($aliases->get('@messages'))),
                    ],
                ]),
            ],
        ],
    ],
];
