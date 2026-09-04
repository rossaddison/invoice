<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Group\GroupController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/group')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GroupController::class, 'index'])
                ->name('group/index'),
        Route::methods([Method::GET, Method::POST], '/group/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GroupController::class, 'add'])
                ->name('group/add'),
        Route::methods([Method::GET, Method::POST], '/group/edit/{id}')
                ->name('group/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GroupController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/group/delete/{id}')
                ->name('group/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GroupController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/group/view/{id}')
                ->name('group/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GroupController::class, 'view']),
    ), // invoice
];
