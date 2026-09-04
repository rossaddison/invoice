<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\InvItem\InvItemController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::methods([Method::GET, Method::POST], '/invitem/view/{id}')
                ->name('invitem/view')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvItemController::class, 'view']),
        Route::methods([Method::POST], '/invitem/addProduct')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemController::class, 'addProduct'])
                ->name('invitem/addProduct'),
        Route::methods([Method::POST], '/invitem/addTask')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemController::class, 'addTask'])
                ->name('invitem/addTask'),
        Route::methods([Method::GET, Method::POST], '/invitem/editProduct/{id}')
                ->name('invitem/editProduct')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemController::class, 'editProduct']),
        Route::methods([Method::GET, Method::POST], '/invitem/editTask/{id}')
                ->name('invitem/editTask')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemController::class, 'editTask']),
        Route::methods([Method::GET, Method::POST], '/invitem/delete/{id}')
                ->name('invitem/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/invitem/multiple')
                ->name('invitem/multiple')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvItemController::class, 'multiple']),
    ), // invoice
];
