<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Import\ImportController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::methods([Method::GET, Method::POST], '/import/index')
                ->name('import/index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ImportController::class, 'index']),

            Route::methods([Method::GET, Method::POST], '/import/testconnection')
                ->name('import/testconnection')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ImportController::class, 'testConnection']),

            Route::methods([Method::GET, Method::POST], '/import/invoiceplane')
                ->name('import/invoiceplane')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ImportController::class, 'invoiceplane']),
        ), // invoice
];
