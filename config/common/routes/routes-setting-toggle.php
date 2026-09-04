<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Setting\SettingToggleController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::methods(
            [Method::GET, Method::POST],
            '/setting/listlimit/{setting_id}/{limit}/{origin}'
        )
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([SettingToggleController::class, 'listlimit'])
                ->name('setting/listlimit'),
        Route::methods([Method::GET, Method::POST], '/setting/draft/{setting_id}')
                ->name('setting/draft')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingToggleController::class,
                    'invDraftHasNumberSwitch']),
        Route::methods([Method::GET, Method::POST], '/setting/autoClient')
                ->name('setting/autoClient')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingToggleController::class, 'autoClient']),
        Route::methods([Method::GET, Method::POST], '/setting/markSent/{setting_id}')
                ->name('setting/markSent')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingToggleController::class, 'markSent']),
        Route::methods([Method::GET, Method::POST], '/setting/toggleinvsentlogcolumn')
                ->name('setting/toggleinvsentlogcolumn')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingToggleController::class,
                    'unhideOrHideToggleInvSentLogColumn']),
        Route::methods([Method::GET, Method::POST], '/setting/visible/{origin}')
                ->name('setting/visible')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingToggleController::class, 'visible']),
        Route::methods([Method::GET, Method::POST], '/setting/gridStickyHeader/{origin}')
                ->name('setting/gridStickyHeader')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingToggleController::class, 'gridStickyHeader']),
        Route::methods([Method::GET, Method::POST], '/setting/navbarSticky/{origin}')
                ->name('setting/navbarSticky')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingToggleController::class, 'navbarSticky']),
    ), // invoice
];
