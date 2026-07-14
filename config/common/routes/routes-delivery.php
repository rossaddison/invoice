<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Delivery\DeliveryController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/delivery')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryController::class, 'index'])
                ->name('delivery/index'),

            // Add
            Route::methods([Method::GET, Method::POST], '/delivery/add/{inv_id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryController::class, 'add'])
                ->name('delivery/add'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/delivery/edit/{id}')
                ->name('delivery/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/delivery/delete/{id}')
                ->name('delivery/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/delivery/view/{id}')
                ->name('delivery/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryController::class, 'view']),
        ), // invoice
];
