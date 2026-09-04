<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Payment\PaymentController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::methods([Method::GET, Method::POST], '/payment/add')
                ->name('payment/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentController::class, 'add']),
        Route::methods([Method::GET, Method::POST], '/payment/edit/{id}')
                ->name('payment/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/payment/delete/{id}')
                ->name('payment/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/payment/view/{id}')
                ->name('payment/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([PaymentController::class, 'view']),
        Route::get('/payment[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_PAYMENT))
                ->action([PaymentController::class, 'index'])
                ->name('payment/index'),
        Route::get('/user_client_payments[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PaymentController::class, 'guest'])
                ->name('payment/guest'),
        Route::get('/online_log[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_PAYMENT))
                ->action([PaymentController::class, 'onlineLog'])
                ->name('payment/onlineLog'),
        Route::get('/guest_online_log[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::VIEW_PAYMENT))
                ->action([PaymentController::class, 'guestOnlineLog'])
                ->name('payment/guestOnlineLog'),
    ), // invoice
];
