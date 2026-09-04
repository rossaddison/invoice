<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\CustomValue\CustomValueController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/customvalue')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomValueController::class, 'index'])
                ->name('customvalue/index'),
        Route::methods([Method::GET, Method::POST], '/customvalue/field/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomValueController::class, 'field'])
                ->name('customvalue/field'),
        Route::methods([Method::GET, Method::POST], '/customvalue/new/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomValueController::class, 'new'])
                ->name('customvalue/new'),
        Route::methods([Method::GET, Method::POST], '/customvalue/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomValueController::class, 'add'])
                ->name('customvalue/add'),
        Route::methods([Method::GET, Method::POST], '/customvalue/edit/{id}')
                ->name('customvalue/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomValueController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/customvalue/delete/{id}')
                ->name('customvalue/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomValueController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/customvalue/view/{id}')
                ->name('customvalue/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomValueController::class, 'view']),
    ), // invoice
];
