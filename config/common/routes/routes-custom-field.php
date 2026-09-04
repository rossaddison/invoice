<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\CustomField\CustomFieldController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/customfield[/page/{page:\d+}]')
                ->name('customfield/index')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomFieldController::class, 'index']),
        Route::methods([Method::GET, Method::POST], '/customfield/add')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomFieldController::class, 'add'])
                ->name('customfield/add'),
        Route::methods([Method::GET, Method::POST], '/customfield/edit/{id}')
                ->name('customfield/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomFieldController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/customfield/delete/{id}')
                ->name('customfield/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomFieldController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/customfield/view/{id}')
                ->name('customfield/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([CustomFieldController::class, 'view']),
    ), // invoice
];
