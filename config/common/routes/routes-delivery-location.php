<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\DeliveryLocation\DeliveryLocationController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::methods([Method::GET, Method::POST], '/del/view/{id}')
                ->name('del/view')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([DeliveryLocationController::class, 'view']),

            Route::get('/del[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryLocationController::class, 'index'])
                ->name('del/index'),

            Route::methods([Method::GET, Method::POST],
                    '/del/add/{client_id}[/{origin}/{origin_id}/{action}]')
                ->name('del/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryLocationController::class, 'add']),

// arguments eg. {id},[query parameters to build a varying return url
// i.e. {origin}, {origin_id}, {action}]
            Route::methods([Method::GET, Method::POST],
                    '/del/edit/{id}[/{origin}/{origin_id}/{action}]')
                ->name('del/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryLocationController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/del/delete/{id}')
                ->name('del/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DeliveryLocationController::class, 'delete']),
        ), // invoice
];
