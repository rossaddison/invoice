<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Upload\UploadController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/upload')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UploadController::class, 'index'])
                ->name('upload/index'),

            Route::methods([Method::GET, Method::POST],
                    '/upload/add[/{origin}/{origin_id}/{action}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UploadController::class, 'add'])
                ->name('upload/add'),

            Route::methods([Method::GET, Method::POST],
                    '/upload/edit/{id}[/{origin}/{origin_id}/{action}]')
                ->name('upload/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UploadController::class, 'edit']),

            Route::methods([Method::GET, Method::POST],
                    '/upload/delete/{id}[/{origin}/{origin_id}/{action}]')
                ->name('upload/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UploadController::class, 'delete']),

            Route::methods([Method::GET, Method::POST],
                    '/upload/view/{id}[/{origin}/{origin_id}/{action}]')
                ->name('upload/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([UploadController::class, 'view']),
        ), // invoice
];
