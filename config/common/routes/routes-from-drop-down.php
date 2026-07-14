<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\FromDropDown\FromDropDownController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/from')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FromDropDownController::class, 'index'])
                ->name('from/index'),

            Route::methods([Method::GET, Method::POST], '/from/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FromDropDownController::class, 'add'])
                ->name('from/add'),

            Route::methods([Method::GET, Method::POST], '/from/edit/{id}')
                ->name('from/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FromDropDownController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/from/delete/{id}')
                ->name('from/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FromDropDownController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/from/view/{id}')
                ->name('from/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([FromDropDownController::class, 'view']),
        ), // invoice
];
