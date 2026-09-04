<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\ProductClient\ProductClientController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

        // ProductClient
        Route::methods([Method::GET, Method::POST], '/productclient/add')
            ->middleware(RoutePermission::check(Permissions::EDIT_INV))
            ->action([ProductClientController::class, 'add'])
            ->name('productclient/add'),
        Route::methods([Method::GET, Method::POST], '/productclient/associate-multiple')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductClientController::class, 'associateMultiple'])
                ->name('productclient/associate-multiple'),
        Route::methods([Method::GET, Method::POST], '/productclient/edit/{id}')
                ->name('productclient/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductClientController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/productclient/delete/{id}')
                ->name('productclient/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductClientController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/productclient/view/{id}')
                ->name('productclient/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProductClientController::class, 'view']),
    ), // invoice
];
