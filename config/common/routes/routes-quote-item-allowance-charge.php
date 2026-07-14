<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            // QuoteItemAllowanceCharge
            Route::get('/quoteitemallowancecharge')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemAllowanceChargeController::class, 'index'])
                ->name('quoteitemallowancecharge/index'),

            // Add
            Route::methods([Method::GET, Method::POST],
                    '/quoteitemallowancecharge/add/{quote_item_id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemAllowanceChargeController::class, 'add'])
                ->name('quoteitemallowancecharge/add'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/quoteitemallowancecharge/edit/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemAllowanceChargeController::class, 'edit'])
                ->name('quoteitemallowancecharge/edit'),

            Route::methods([Method::GET, Method::POST], '/quoteitemallowancecharge/delete/{id}')
                ->name('quoteitemallowancecharge/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemAllowanceChargeController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/quoteitemallowancecharge/view/{id}')
                ->name('quoteitemallowancecharge/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteItemAllowanceChargeController::class, 'view']),
        ), // invoice
];
