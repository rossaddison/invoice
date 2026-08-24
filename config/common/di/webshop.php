<?php

declare(strict_types=1);

use App\Webshop\StorefrontViewParameters;
use Yiisoft\Definitions\Reference;
use Yiisoft\Yii\View\Renderer\LayoutSpecificInjections;

/**
 * `LayoutSpecificInjections` needs constructor arguments (which layout it
 * scopes to, plus the injection itself) that `Reference::to()` alone can't
 * express — `config/common/params.php`'s own `yiisoft/yii-view-renderer.
 * injections` array only accepts plain objects or DI id strings
 * (`WebViewRenderer::getPreparedInjections()`/`InjectionContainer::get()`),
 * not an inline array-shaped definition, so this class-keyed definition
 * lives here instead and gets pulled in via `Reference::to
 * (LayoutSpecificInjections::class)` from that params.php array.
 */
return [
    LayoutSpecificInjections::class => [
        'class' => LayoutSpecificInjections::class,
        '__construct()' => [
            '@views/layout/templates/storefront/main.php',
            Reference::to(StorefrontViewParameters::class),
        ],
    ],
];
