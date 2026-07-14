<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Generator\GeneratorController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/generator')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class, 'index'])
                ->name('generator/index'),

            Route::methods([Method::GET, Method::POST], '/generator/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class, 'add'])
                ->name('generator/add'),

            Route::methods([Method::GET, Method::POST], '/generator/edit/{id}')
                ->name('generator/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/generator/delete/{id}')
                ->name('generator/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/generator/view/{id}')
                ->name('generator/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class, 'view']),

            Route::methods([Method::GET, Method::POST], '/generator/mapper/{id}')
                ->name('generator/mapper')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class, 'mapper']),

            Route::methods([Method::GET, Method::POST], '/generator/scope/{id}')
                ->name('generator/scope')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class, 'scope']),

            Route::methods([Method::GET, Method::POST], '/generator/_index_adv_paginator/{id}')
                ->name('generator/_index_adv_paginator')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class, '_index_adv_paginator']),

            Route::methods([Method::GET, Method::POST],
                    '/generator/_index_adv_paginator_with_filter/{id}')
                ->name('generator/_index_adv_paginator_with_filter')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class,
                    '_index_adv_paginator_with_filter']),

            Route::methods([Method::GET, Method::POST], '/generator/quickViewSchema')
                ->name('generator/quickViewSchema')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorController::class, 'quickViewSchema']),
        ), // invoice
];
