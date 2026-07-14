<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Client\ClientController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(

            Route::get('/client[/page/{page:\d+}[/active/{active}]]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'index'])
                ->name('client/index'),

            Route::methods([Method::GET, Method::POST], '/client/add/{origin}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'add'])
                ->name('client/add'),

            Route::methods([Method::GET, Method::POST], '/edit-a-client/{id}/{origin}')
                ->name('client/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/client/editSubmit')
                ->name('client/editSubmit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'editSubmit']),

            Route::methods([Method::GET, Method::POST], '/client/delete/{id}')
                ->name('client/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'delete']),

            Route::methods([Method::GET, Method::POST], '/client/guest')
                ->name('client/guest')
                ->middleware(RoutePermission::check(Permissions::EDIT_CLIENT_PEPPOL))
                ->action([ClientController::class, 'guest']),

            Route::methods([Method::GET, Method::POST], '/client/saveClientNoteNew')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'saveClientNoteNew'])
                ->name('client/saveClientNoteNew'),

            Route::methods([Method::GET, Method::POST], '/client/deleteClientNote')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'deleteClientNote'])
                ->name('client/deleteClientNote'),

            Route::methods([Method::GET, Method::POST], '/client/loadClientNotes')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'loadClientNotes'])
                ->name('client/loadClientNotes'),

            Route::methods([Method::GET, Method::POST],
                    '/client/view/{id}[/page/{page:\d+}[/status/{status}]]')
                ->name('client/view')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'view']),

            // Lets staff print a client's home-care QR code on their
            // behalf (many customers are elderly and lack printer access)
            Route::get('/client/printQrCode/{id:\d+}')
                ->name('client/printQrCode')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([ClientController::class, 'printQrCode']),
        ), // invoice
];
