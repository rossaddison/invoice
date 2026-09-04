<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\InvSentLog\InvSentLogController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/invsentlog/guest')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvSentLogController::class, 'guest'])
                ->name('invsentlog/guest'),
        Route::methods([Method::GET, Method::POST], '/invsentlog/view/{id}')
                ->name('invsentlog/view')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvSentLogController::class, 'view']),
        Route::get('/invsentlog')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvSentLogController::class, 'index'])
                ->name('invsentlog/index'),
    ), // invoice
];
