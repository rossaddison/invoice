<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Setting\SettingController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/setting/debug_index[/page{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'debugIndex'])
                ->name('setting/debugIndex'),

            Route::methods([Method::GET, Method::POST], '/setting/save')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'save'])
                ->name('setting/save'),

            Route::methods([Method::GET, Method::POST], '/setting/tab_index[/{active:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'tabIndex'])
                ->name('setting/tabIndex'),

            Route::methods([Method::GET, Method::POST], '/setting/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'add'])
                ->name('setting/add'),

            Route::methods([Method::GET, Method::POST], '/setting/edit/{setting_id}')
                ->name('setting/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/setting/delete/{setting_id}')
                ->name('setting/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'delete']),

            Route::get('/setting/fphgenerate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'fphgenerate'])
                ->name('setting/fphgenerate'),

            Route::get('/setting/checkPhpVersion')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'checkPhpVersionNow'])
                ->name('setting/checkPhpVersion'),

            Route::methods([Method::GET, Method::POST], '/setting/index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'index'])
                ->name('setting/index'),

            Route::methods([Method::GET, Method::POST], '/setting/getCronKey')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'getCronKey'])
                ->name('setting/getCronKey'),

            Route::methods([Method::GET, Method::POST], '/setting/view/{setting_id}')
                ->name('setting/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'view']),

            Route::methods([Method::GET, Method::POST], '/setting/clear')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([SettingController::class, 'clear'])
                ->name('setting/clear'),
        ), // invoice
];
