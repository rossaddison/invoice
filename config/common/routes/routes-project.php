<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Project\ProjectController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/project[/page/{page:\d+}]')
                ->name('project/index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProjectController::class, 'index']),
        Route::methods([Method::GET, Method::POST], '/project/add')
                ->name('project/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProjectController::class, 'add']),
        Route::methods([Method::GET, Method::POST], '/project/edit/{id}')
                ->name('project/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProjectController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/project/delete/{id}')
                ->name('project/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProjectController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/project/view/{id}')
                ->name('project/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ProjectController::class, 'view']),
    ), // invoice
];
