<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Product\ProductController;
use App\Middleware\RoutePermission;
use Yiisoft\DataResponse\Middleware\JsonDataResponseMiddleware;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/product[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductController::class, 'index'])
                ->name('product/index'),
        Route::get('/product/search')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductController::class, 'search'])
                ->name('product/search'),
        Route::methods([Method::GET, Method::POST], '/product/test')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->middleware(JsonDataResponseMiddleware::class)
                ->action([ProductController::class, 'test'])
                ->name('product/test'),
        Route::methods([Method::GET, Method::POST], '/product/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductController::class, 'add'])
                ->name('product/add'),
        Route::methods([Method::GET, Method::POST], '/product/lookup')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductController::class, 'lookup'])
                ->name('product/lookup'),
        Route::methods([Method::GET, Method::POST], '/product/edit/{id}')
                ->name('product/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/product/delete/{id}')
                ->name('product/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/product/view/{id}')
                ->name('product/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductController::class, 'view']),
    ), // invoice
];
