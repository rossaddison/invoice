<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\PurchaseEntry\PurchaseEntryController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/entry')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PurchaseEntryController::class, 'index'])
                ->name('entry/index'),
        Route::methods([Method::GET, Method::POST], '/entry/add')
                ->name('entry/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PurchaseEntryController::class, 'add']),
        Route::methods([Method::GET, Method::POST], '/entry/edit/{id}')
                ->name('entry/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PurchaseEntryController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/entry/delete/{id}')
                ->name('entry/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PurchaseEntryController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/entry/csv-import')
                ->name('entry/csv-import')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PurchaseEntryController::class, 'csvImport']),
        Route::get('/entry/csv-template')
                ->name('entry/csv-template')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PurchaseEntryController::class, 'csvTemplate']),
        Route::get('/entry/view/{id}')
                ->name('entry/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PurchaseEntryController::class, 'view']),
        Route::get('/entry/tax-year-locales')
                ->name('entry/tax-year-locales')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PurchaseEntryController::class, 'taxYearLocales']),
        Route::methods([Method::POST], '/entry/apply-tax-year-locale')
                ->name('entry/apply-tax-year-locale')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PurchaseEntryController::class, 'applyTaxYearLocale']),
    ), // invoice
];
