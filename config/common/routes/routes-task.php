<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Task\TaskController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/task[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaskController::class, 'index'])
                ->name('task/index'),

            Route::methods([Method::GET, Method::POST], '/task/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaskController::class, 'add'])
                ->name('task/add'),

            Route::methods([Method::GET, Method::POST], '/task/selection_inv')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaskController::class, 'selectionInv'])
                ->name('task/selectionInv'),

            Route::methods([Method::GET, Method::POST], '/task/selection_quote')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaskController::class, 'selectionQuote'])
                ->name('task/selectionQuote'),

            Route::methods([Method::GET, Method::POST], '/task/edit/{id}')
                ->name('task/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaskController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/task/delete/{id}')
                ->name('task/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaskController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/task/view/{id}')
                ->name('task/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([TaskController::class, 'view']),
        ), // invoice
];
