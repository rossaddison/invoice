<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Contract\ContractController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::methods([Method::GET, Method::POST], '/contract/view/{id}')
                ->name('contract/view')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([ContractController::class, 'view']),

            Route::get('/contract')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ContractController::class, 'index'])
                ->name('contract/index'),

            Route::methods([Method::GET, Method::POST], '/contract/add/{client_id}')
                ->name('contract/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ContractController::class, 'add']),

            Route::methods([Method::GET, Method::POST], '/contract/edit/{id}')
                ->name('contract/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ContractController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/contract/delete/{id}')
                ->name('contract/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ContractController::class, 'delete']),
        ), // invoice
];
