<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\ClientPeppol\ClientPeppolController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
        Route::get('/clientpeppol')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientPeppolController::class, 'index'])
                ->name('clientpeppol/index'),

        // Add
        Route::methods([Method::GET, Method::POST], '/clientpeppol/add/{client_id}')
            ->middleware(RoutePermission::check(Permissions::EDIT_CLIENT_PEPPOL))
            ->action([ClientPeppolController::class, 'add'])
            ->name('clientpeppol/add'),

        // Edit
        Route::methods([Method::GET, Method::POST], '/clientpeppol/edit/{client_id}')
            ->name('clientpeppol/edit')
            ->middleware(RoutePermission::check(Permissions::EDIT_CLIENT_PEPPOL))
            ->action([ClientPeppolController::class, 'edit']),
        Route::methods([Method::GET, Method::POST], '/clientpeppol/delete/{client_id}')
                ->name('clientpeppol/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_CLIENT_PEPPOL))
                ->action([ClientPeppolController::class, 'delete']),
        Route::methods([Method::GET, Method::POST], '/clientpeppol/view/{client_id}')
                ->name('clientpeppol/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_CLIENT_PEPPOL))
                ->action([ClientPeppolController::class, 'view']),
    ), // invoice
];
