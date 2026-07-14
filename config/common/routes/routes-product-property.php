<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\ProductProperty\ProductPropertyController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            // ProductProperty
            Route::get('/productproperty')
                ->name('productproperty/index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductPropertyController::class, 'index']),

            // Add
            Route::methods([Method::GET, Method::POST], '/productproperty/add/{product_id}')
                ->name('productproperty/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductPropertyController::class, 'add']),

            // Edit
            Route::methods([Method::GET, Method::POST], '/productproperty/edit/{id}')
                ->name('productproperty/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductPropertyController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/productproperty/delete/{id}')
                ->name('productproperty/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductPropertyController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/productproperty/view/{id}')
                ->name('productproperty/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductPropertyController::class, 'view']),
        ), // invoice
];
