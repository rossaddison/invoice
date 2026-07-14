<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\ItemLookup\ItemLookupController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/itemlookup')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ItemLookupController::class, 'index'])
                ->name('itemlookup/index'),

            Route::methods([Method::GET, Method::POST], '/itemlookup/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ItemLookupController::class, 'add'])
                ->name('itemlookup/add'),

            Route::methods([Method::GET, Method::POST], '/itemlookup/edit/{id}')
                ->name('itemlookup/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ItemLookupController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/itemlookup/delete/{id}')
                ->name('itemlookup/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ItemLookupController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/itemlookup/view/{id}')
                ->name('itemlookup/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ItemLookupController::class, 'view']),
        ), // invoice
];
