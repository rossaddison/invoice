<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\CompanyPrivate\CompanyPrivateController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/companyprivate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyPrivateController::class, 'index'])
                ->name('companyprivate/index'),

            Route::methods([Method::GET, Method::POST], '/companyprivate/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyPrivateController::class, 'add'])
                ->name('companyprivate/add'),

            Route::methods([Method::GET, Method::POST], '/companyprivate/edit/{id}')
                ->name('companyprivate/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyPrivateController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/companyprivate/delete/{id}')
                ->name('companyprivate/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyPrivateController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/companyprivate/view/{id}')
                ->name('companyprivate/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CompanyPrivateController::class, 'view']),
        ), // invoice
];
