<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Qa\QaController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/qa[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QaController::class, 'index'])
                ->name('qa/index'),

            Route::methods([Method::GET, Method::POST], '/qa/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QaController::class, 'add'])
                ->name('qa/add'),

            Route::methods([Method::GET, Method::POST], '/qa/edit/{id}')
                ->name('qa/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QaController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/qa/delete/{id}')
                ->name('qa/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QaController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/qa/view/{id}')
                ->name('qa/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([QaController::class, 'view']),
        ), // invoice
];
