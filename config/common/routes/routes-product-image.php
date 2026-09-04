<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\ProductImage\ProductImageController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/productimage')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductImageController::class, 'index'])
                ->name('productimage/index'),
        Route::methods([Method::GET, Method::POST], '/productimage/add/{product_id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductImageController::class, 'add'])
                ->name('productimage/add'),
        Route::methods([Method::GET, Method::POST], '/productimage/edit/{id}')
                ->name('productimage/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductImageController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/productimage/delete/{id}')
                ->name('productimage/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductImageController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/productimage/view/{id}')
                ->name('productimage/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductImageController::class, 'view']),
    ), // invoice
];
