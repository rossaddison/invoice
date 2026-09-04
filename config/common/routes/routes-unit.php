<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Unit\UnitController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/unit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitController::class, 'index'])
                ->name('unit/index'),
        Route::methods([Method::GET, Method::POST], '/unit/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitController::class, 'add'])
                ->name('unit/add'),
        Route::methods([Method::GET, Method::POST], '/unit/edit/{unit_id}')
                ->name('unit/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/unit/delete/{unit_id}')
                ->name('unit/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/unit/view/{unit_id}')
                ->name('unit/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UnitController::class, 'view']),
    ), // invoice
];
