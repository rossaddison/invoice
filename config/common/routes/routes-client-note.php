<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\ClientNote\ClientNoteController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
// ************************************ pVI's *********************************
            Route::methods([Method::GET, Method::POST], '/clientnote/view/{id}')
                ->name('clientnote/view')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([ClientNoteController::class, 'view']),

            Route::get('/clientnote')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientNoteController::class, 'index'])
                ->name('clientnote/index'),

            Route::methods([Method::GET, Method::POST], '/clientnote/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientNoteController::class, 'add'])
                ->name('clientnote/add'),

            Route::methods([Method::GET, Method::POST], '/clientnote/edit/{id}')
                ->name('clientnote/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientNoteController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/clientnote/delete/{id}')
                ->name('clientnote/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientNoteController::class, 'delete']),
        ), // invoice
];
