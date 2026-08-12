<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Dwelling\DwellingController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/dwelling[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DwellingController::class, 'index'])
                ->name('dwelling/index'),

            Route::methods([Method::GET, Method::POST], '/dwelling/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DwellingController::class, 'add'])
                ->name('dwelling/add'),

            Route::methods([Method::GET, Method::POST], '/dwelling/edit/{id}')
                ->name('dwelling/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DwellingController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/dwelling/delete/{id}')
                ->name('dwelling/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DwellingController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/dwelling/view/{id}')
                ->name('dwelling/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([DwellingController::class, 'view']),
        ), // invoice
];
