<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\DeliveryParty\DeliveryPartyController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/deliveryparty')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryPartyController::class, 'index'])
                ->name('deliveryparty/index'),

            // Add
            Route::methods([Method::GET, Method::POST], '/deliveryparty/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryPartyController::class, 'add'])
                ->name('deliveryparty/add'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/deliveryparty/edit/{id}')
                ->name('deliveryparty/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryPartyController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/deliveryparty/delete/{id}')
                ->name('deliveryparty/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryPartyController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/deliveryparty/view/{id}')
                ->name('deliveryparty/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryPartyController::class, 'view']),
        ), // invoice
];
