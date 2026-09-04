<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\PostalAddress\PostalAddressController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

        // PostalAddress
        Route::get('/postaladdress')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->name('postaladdress/index')
            ->action([PostalAddressController::class, 'index']),

        // Add
        Route::methods(
            [Method::GET, Method::POST],
            '/postaladdress/add/{client_id}[/{origin}/{origin_id}/{action}]'
        )
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([PostalAddressController::class, 'add'])
            ->name('postaladdress/add'),

        // Edit
        Route::methods(
            [Method::GET, Method::POST],
            '/postaladdress/edit/{id}[/{origin}/{origin_id}/{action}]'
        )
            ->name('postaladdress/edit')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([PostalAddressController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/postaladdress/delete/{id}')
                ->name('postaladdress/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PostalAddressController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/postaladdress/view/{id}')
                ->name('postaladdress/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PostalAddressController::class, 'view']),
    ), // invoice
];
