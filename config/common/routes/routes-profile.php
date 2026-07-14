<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Profile\ProfileController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/profile')
                ->name('profile/index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProfileController::class, 'index']),

            Route::methods([Method::GET, Method::POST], '/profile/add')
                ->name('profile/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProfileController::class, 'add']),

            Route::methods([Method::GET, Method::POST], '/profile/edit/{id}')
                ->name('profile/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProfileController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/profile/delete/{id}')
                ->name('profile/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProfileController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/profile/view/{id}')
                ->name('profile/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProfileController::class, 'view']),
        ), // invoice
];
