<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            // QuoteAllowanceCharge
            Route::get('/quoteallowancecharge[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteAllowanceChargeController::class, 'index'])
                ->name('quoteallowancecharge/index'),

            // Add
            Route::methods([Method::GET, Method::POST], '/quoteallowancecharge/add/{quote_id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteAllowanceChargeController::class, 'add'])
                ->name('quoteallowancecharge/add'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/quoteallowancecharge/edit/{id}')
                ->name('quoteallowancecharge/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteAllowanceChargeController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/quoteallowancecharge/delete/{id}')
                ->name('quoteallowancecharge/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteAllowanceChargeController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/quoteallowancecharge/view/{id}')
                ->name('quoteallowancecharge/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QuoteAllowanceChargeController::class, 'view']),
        ), // invoice
];
