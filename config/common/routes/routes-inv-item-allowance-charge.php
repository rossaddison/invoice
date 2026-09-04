<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

        // InvItemAllowanceCharge
        Route::get('/invitemallowancecharge')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([InvItemAllowanceChargeController::class, 'index'])
            ->name('invitemallowancecharge/index'),

        // Add
        Route::methods(
            [Method::GET, Method::POST],
            '/invitemallowancecharge/add/{inv_item_id}'
        )
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([InvItemAllowanceChargeController::class, 'add'])
            ->name('invitemallowancecharge/add'),

        // Edit
        Route::methods(
            [Method::GET, Method::POST],
            '/invitemallowancecharge/edit/{id}'
        )
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([InvItemAllowanceChargeController::class, 'edit'])
            ->name('invitemallowancecharge/edit'),
        Route::methods(
            [Method::GET, Method::POST],
            '/invitemallowancecharge/delete/{id}'
        )
                ->name('invitemallowancecharge/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemAllowanceChargeController::class, 'delete']),
        Route::methods(
            [Method::GET, Method::POST],
            '/invitemallowancecharge/view/{id}'
        )
                ->name('invitemallowancecharge/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemAllowanceChargeController::class, 'view']),
    ), // invoice
];
