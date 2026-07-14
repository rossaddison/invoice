<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\PaymentMethod\PaymentMethodController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/paymentmethod')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentMethodController::class, 'index'])
                ->name('paymentmethod/index'),

            Route::methods([Method::GET, Method::POST], '/paymentmethod/add')
                ->name('paymentmethod/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentMethodController::class, 'add']),

            Route::methods([Method::GET, Method::POST], '/paymentmethod/edit/{id}')
                ->name('paymentmethod/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentMethodController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/paymentmethod/delete/{id}')
                ->name('paymentmethod/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentMethodController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/paymentmethod/view/{id}')
                ->name('paymentmethod/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentMethodController::class, 'view']),
        ), // invoice
];
