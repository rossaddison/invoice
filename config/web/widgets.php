<?php

declare(strict_types=1);

use App\Widget\FormFields;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Definitions\Reference;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;
use Yiisoft\Yii\DataView\YiiRouter\UrlParameterProvider;

return [
    FormFields::class => [
        '__construct()' => [
            Reference::to(TranslatorInterface::class),
            Reference::to(SettingRepository::class),
        ],
    ],

    GridView::class => [
        'urlParameterProvider()' => [
            Reference::to(UrlParameterProvider::class),
        ],
        'urlCreator()' => [
            Reference::to(UrlCreator::class),
        ],
        'ignoreMissingPage()' => [true],
    ],
];
