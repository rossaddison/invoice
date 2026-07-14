<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\InvItem\InvItemHtmxController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::methods([Method::POST], '/invitemhtmx/addProduct')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemHtmxController::class, 'addProduct'])
                ->name('invitemhtmx/addProduct'),

            Route::methods([Method::POST], '/invitemhtmx/addTask')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemHtmxController::class, 'addTask'])
                ->name('invitemhtmx/addTask'),
        ), // invoice
];
