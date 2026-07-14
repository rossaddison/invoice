<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\UnitPeppol\UnitPeppolController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            // UnitPeppol
            Route::get('/unitpeppol')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitPeppolController::class, 'index'])
                ->name('unitpeppol/index'),

            // Add
            Route::methods([Method::GET, Method::POST], '/unitpeppol/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitPeppolController::class, 'add'])
                ->name('unitpeppol/add'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/unitpeppol/edit/{id}')
                ->name('unitpeppol/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitPeppolController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/unitpeppol/delete/{id}')
                ->name('unitpeppol/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitPeppolController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/unitpeppol/view/{id}')
                ->name('unitpeppol/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitPeppolController::class, 'view']),
        ), // invoice
];
