<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\TaxRate\TaxRateController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/taxrate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaxRateController::class, 'index'])
                ->name('taxrate/index'),
        Route::methods([Method::GET, Method::POST], '/taxrate/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaxRateController::class, 'add'])
                ->name('taxrate/add'),
        Route::methods([Method::GET, Method::POST], '/taxrate/edit/{tax_rate_id}')
                ->name('taxrate/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaxRateController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/taxrate/delete/{tax_rate_id}')
                ->name('taxrate/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaxRateController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/taxrate/view/{tax_rate_id}')
                ->name('taxrate/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaxRateController::class, 'view']),
    ), // invoice
];
