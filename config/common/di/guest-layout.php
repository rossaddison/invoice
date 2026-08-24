<?php

declare(strict_types=1);

use App\ViewInjection\GuestLayoutViewParameters;
use Yiisoft\Definitions\Reference;
use Yiisoft\Yii\View\Renderer\LayoutSpecificInjections;

/**
 * Same shape as config/common/di/webshop.php's own LayoutSpecificInjections
 * binding — that class needs constructor arguments (which layout it scopes
 * to, plus the injection itself) Reference::to() alone can't express, and
 * `LayoutSpecificInjections::class` itself is already bound there (scoped
 * to the storefront layout), so this one is registered under its own
 * string container id instead and pulled in via
 * Reference::to('guestLayoutSpecificInjections') from
 * config/common/params.php's own injections array.
 */
return [
    'guestLayoutSpecificInjections' => [
        'class' => LayoutSpecificInjections::class,
        '__construct()' => [
            '@views/layout/guest.php',
            Reference::to(GuestLayoutViewParameters::class),
        ],
    ],
];
