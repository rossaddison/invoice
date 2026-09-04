<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Company\CompanyController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/company')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyController::class, 'index'])
                ->name('company/index'),
        Route::methods([Method::GET, Method::POST], '/company/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyController::class, 'add'])
                ->name('company/add'),
        Route::methods([Method::GET, Method::POST], '/company/edit/{id}')
                ->name('company/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/company/delete/{id}')
                ->name('company/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/company/view/{id}')
                ->name('company/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyController::class, 'view']),
    ), // invoice
];
