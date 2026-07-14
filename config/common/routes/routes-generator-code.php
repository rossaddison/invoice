<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Generator\GeneratorCodeController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::methods([Method::GET, Method::POST], '/generator/entity/{id}')
                ->name('generator/entity')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorCodeController::class, 'entity']),

            Route::methods([Method::GET, Method::POST], '/generator/repo/{id}')
                ->name('generator/repo')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorCodeController::class, 'repo']),

            Route::methods([Method::GET, Method::POST], '/generator/service/{id}')
                ->name('generator/service')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorCodeController::class, 'service']),

            Route::methods([Method::GET, Method::POST], '/generator/controller/{id}')
                ->name('generator/controller')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorCodeController::class, 'controller']),

            Route::methods([Method::GET, Method::POST], '/generator/form/{id}')
                ->name('generator/form')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorCodeController::class, 'form']),

            Route::methods([Method::GET, Method::POST], '/generator/_index/{id}')
                ->name('generator/_index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorCodeController::class, 'generatorIndex']),

            Route::methods([Method::GET, Method::POST], '/generator/_form/{id}')
                ->name('generator/_form')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorCodeController::class, 'generatorForm']),

            Route::methods([Method::GET, Method::POST], '/generator/_view/{id}')
                ->name('generator/_view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorCodeController::class, 'generatorView']),

            Route::methods([Method::GET, Method::POST], '/generator/_route/{id}')
                ->name('generator/_route')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorCodeController::class, 'generatorRoute']),
        ), // invoice
];
