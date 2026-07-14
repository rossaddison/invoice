<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\CategorySecondary\CategorySecondaryController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/categorysecondary[/page/{page:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategorySecondaryController::class, 'index'])
                ->name('categorysecondary/index'),

            // Add
            Route::methods([Method::GET, Method::POST], '/categorysecondary/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategorySecondaryController::class, 'add'])
                ->name('categorysecondary/add'),

            // Edit
            Route::methods([Method::GET, Method::POST], '/categorysecondary/edit/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategorySecondaryController::class, 'edit'])
                ->name('categorysecondary/edit'),

            Route::methods([Method::GET, Method::POST], '/categorysecondary/delete/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategorySecondaryController::class, 'delete'])
                ->name('categorysecondary/delete'),

            Route::methods([Method::GET, Method::POST], '/categorysecondary/view/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CategorySecondaryController::class, 'view'])
                ->name('categorysecondary/view'),
        ), // invoice
];
