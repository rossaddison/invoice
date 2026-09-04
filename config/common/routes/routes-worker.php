<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Worker\WorkerController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/worker')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([WorkerController::class, 'index'])
                ->name('worker/index'),
        Route::methods([Method::GET, Method::POST], '/worker/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([WorkerController::class, 'add'])
                ->name('worker/add'),
        Route::methods([Method::GET, Method::POST], '/worker/edit/{worker_id}')
                ->name('worker/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([WorkerController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/worker/delete/{worker_id}')
                ->name('worker/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([WorkerController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/worker/view/{worker_id}')
                ->name('worker/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([WorkerController::class, 'view']),
    ), // invoice
];
