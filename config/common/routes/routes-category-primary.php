<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\CategoryPrimary\CategoryPrimaryController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
Route::get('/categoryprimary[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategoryPrimaryController::class, 'index'])
                ->name('categoryprimary/index'),

            // Add
            Route::methods([Method::GET, Method::POST], '/categoryprimary/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategoryPrimaryController::class, 'add'])
                ->name('categoryprimary/add'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/categoryprimary/edit/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategoryPrimaryController::class, 'edit'])
                ->name('categoryprimary/edit'),

            Route::methods([Method::GET, Method::POST], '/categoryprimary/delete/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategoryPrimaryController::class, 'delete'])
                ->name('categoryprimary/delete'),

            Route::methods([Method::GET, Method::POST], '/categoryprimary/view/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategoryPrimaryController::class, 'view'])
                ->name('categoryprimary/view'),
        ), // invoice
];
