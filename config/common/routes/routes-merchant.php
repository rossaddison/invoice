<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Merchant\MerchantController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/merchant')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([MerchantController::class, 'index'])
                ->name('merchant/index'),

            Route::methods([Method::GET, Method::POST], '/merchant/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([MerchantController::class, 'add'])
                ->name('merchant/add'),

            Route::methods([Method::GET, Method::POST], '/merchant/edit/{id}')
                ->name('merchant/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([MerchantController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/merchant/delete/{id}')
                ->name('merchant/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([MerchantController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/merchant/view/{id}')
                ->name('merchant/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([MerchantController::class, 'view']),
        ), // invoice
];
