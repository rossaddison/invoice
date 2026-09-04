<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\GeneratorRelation\GeneratorRelationController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/generatorrelation')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorRelationController::class, 'index'])
                ->name('generatorrelation/index'),
        Route::methods([Method::GET, Method::POST], '/generatorrelation/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorRelationController::class, 'add'])
                ->name('generatorrelation/add'),
        Route::methods([Method::GET, Method::POST], '/generatorrelation/edit/{id}')
                ->name('generatorrelation/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorRelationController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/generatorrelation/delete/{id}')
                ->name('generatorrelation/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorRelationController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/generatorrelation/view/{id}')
                ->name('generatorrelation/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([GeneratorRelationController::class, 'view']),
    ), // invoice
];
