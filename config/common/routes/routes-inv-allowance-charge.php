<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

        // InvAllowanceCharge
        Route::get('/invallowancecharge[/page/{page:\d+}]')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([InvAllowanceChargeController::class, 'index'])
            ->name('invallowancecharge/index'),

        // Add
        Route::methods([Method::GET, Method::POST], '/invallowancecharge/add/{inv_id}')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([InvAllowanceChargeController::class, 'add'])
            ->name('invallowancecharge/add'),

        // Edit
        Route::methods([Method::GET, Method::POST], '/invallowancecharge/edit/{id}')
            ->name('invallowancecharge/edit')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([InvAllowanceChargeController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/invallowancecharge/delete/{id}')
                ->name('invallowancecharge/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvAllowanceChargeController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/invallowancecharge/view/{id}')
                ->name('invallowancecharge/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvAllowanceChargeController::class, 'view']),
    ), // invoice
];
