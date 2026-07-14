<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\PaymentPeppol\PaymentPeppolController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
// Add
            Route::methods([Method::GET, Method::POST], '/paymentpeppol/add/{inv_id}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([PaymentPeppolController::class, 'add'])
                ->name('paymentpeppol/add'),

            Route::get('/paymentpeppol')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentPeppolController::class, 'index'])
                ->name('paymentpeppol/index'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/paymentpeppol/edit/{id}')
                ->name('paymentpeppol/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentPeppolController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/paymentpeppol/delete/{id}')
                ->name('paymentpeppol/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentPeppolController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/paymentpeppol/view/{id}')
                ->name('paymentpeppol/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentPeppolController::class, 'view']),
        ), // invoice
];
